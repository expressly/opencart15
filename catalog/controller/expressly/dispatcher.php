<?php

use Catalog\CommonController;
use Expressly\Entity\Address;
use Expressly\Entity\Customer;
use Expressly\Entity\Email;
use Expressly\Entity\Invoice;
use Expressly\Entity\Order;
use Expressly\Entity\Phone;
use Expressly\Entity\Route;
use Expressly\Exception\ExceptionFormatter;
use Expressly\Exception\GenericException;
use Expressly\Presenter\BatchCustomerPresenter;
use Expressly\Presenter\BatchInvoicePresenter;
use Expressly\Presenter\CustomerMigratePresenter;
use Expressly\Presenter\PingPresenter;
use Expressly\Presenter\RegisteredPresenter;
use Expressly\Route\BatchCustomer;
use Expressly\Route\BatchInvoice;
use Expressly\Route\CampaignMigration;
use Expressly\Route\CampaignPopup;
use Expressly\Route\Ping;
use Expressly\Route\Registered;
use Expressly\Route\UserData;

require_once __DIR__ . '/../../../expressly/includes.php';

class ControllerExpresslyDispatcher extends CommonController
{
    public function index()
    {
        $query = $this->request->get['query'];
        $route = $this->getResolver()->process($query);

        if ($route instanceof Route) {
            switch ($route->getName()) {
                case Ping::getName():
                    $this->ping();
                    break;
                case Registered::getName():
                    $this->registered();
                    break;
                case UserData::getName():
                    $data = $route->getData();
                    $this->response->addHeader('Content-Type: application/json');
                    $this->response->setOutput(json_encode($this->retrieveUserByEmail($data['email'])));
                    break;
                case CampaignPopup::getName():
                    $data = $route->getData();
                    $this->redirect($this->url->link('expressly/migrate/popup', "uuid={$data['uuid']}", 'SSL'));
                    break;
                case CampaignMigration::getName():
                    $data = $route->getData();
                    $this->redirect($this->url->link('expressly/migrate/complete', "uuid={$data['uuid']}", 'SSL'));
                    break;
                case BatchCustomer::getName():
                    $this->response->addHeader('Content-Type: application/json');
                    $this->response->setOutput(json_encode($this->getBulkCustomers()));
                    break;
                case BatchInvoice::getName():
                    $this->response->addHeader('Content-Type: application/json');
                    $this->response->setOutput(json_encode($this->getBulkInvoices()));
                    break;
            }
        } else {
            if (http_response_code() != 401) {
                $this->redirect('/');
            }
        }
    }

    private function ping()
    {
        $presenter = new PingPresenter();
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($presenter->toArray()));
    }

    private function registered()
    {
        $presenter = new RegisteredPresenter();
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($presenter->toArray()));
    }

    private function retrieveUserByEmail($emailAddress)
    {
        $this->load->model('account/customer');
        $ocCustomer = $this->model_account_customer->getCustomerByEmail($emailAddress);

        if (empty($ocCustomer)) {
            header('HTTP/1.1 404 Unauthorized', true, 404);
            return array();
        }

        /*
         * Mock user object to be able to retrieve address(es)
         * Side affect - user is logged in
         */
        $this->session->data['customer_id'] = $ocCustomer['customer_id'];
        $registryCustomer = new \Customer($this->registry);
        $this->customer = $registryCustomer;

        $customer = new Customer();
        $customer
            ->setFirstName($ocCustomer['firstname'])
            ->setLastName($ocCustomer['lastname'])
            ->setDateUpdated(new \DateTime($ocCustomer['date_added']));

        $email = new Email();
        $email
            ->setAlias('default')
            ->setEmail($emailAddress);
        $customer->addEmail($email);

        $phone = new Phone();
        $phone
            ->setNumber($ocCustomer['telephone'])
            ->setType(Phone::PHONE_TYPE_HOME);
        $customer->addPhone($phone);
        $phoneIndex = $customer->getPhoneIndex($phone);

        $this->load->model('account/address');
        foreach ($this->model_account_address->getAddresses() as $ocAddress) {
            $address = new Address();
            $address
                ->setAlias($ocAddress['address_1'])
                ->setFirstName($ocAddress['firstname'])
                ->setLastName($ocAddress['lastname'])
                ->setCompanyName($ocAddress['company'])
                ->setAddress1($ocAddress['address_1'])
                ->setAddress2($ocAddress['address_2'])
                ->setCity($ocAddress['city'])
                ->setZip($ocAddress['postcode'])
                ->setStateProvince($ocAddress['zone_code'])
                ->setCountry($ocAddress['iso_code_3'])
                ->setPhonePosition($phoneIndex);

            $primary = $ocCustomer['address_id'] == $ocAddress['address_id'];
            if ($primary) {
                $customer->setTaxNumber($ocAddress['tax_id']);
            }

            $customer->addAddress($address, (bool)$primary, Address::ADDRESS_BOTH);
        }

        $this->customer->logout();

        $app = $this->getApp();
        $merchant = $app['merchant.provider']->getMerchant();
        $response = new CustomerMigratePresenter($merchant, $customer, $emailAddress, $ocCustomer['customer_id']);

        return $response->toArray();
    }

    public function getBulkCustomers()
    {
        $json = file_get_contents('php://input');
        $json = json_decode($json);

        $existing = array();
        $deleted = array();
        $pending = array();

        try {
            if (!property_exists($json, 'emails')) {
                throw new GenericException('Invalid JSON input');
            }

            $this->load->model('account/customer');

            foreach ($json->emails as $email) {
                $ocCustomer = $this->model_account_customer->getCustomerByEmail($email);

                if (empty($ocCustomer)) {
                    continue;
                }
                if (!$ocCustomer['status']) {
                    $deleted[] = $email;
                } else if (!$ocCustomer['approved']) {
                    $pending[] = $email;
                } else {
                    $existing[] = $email;
                }
            }
        } catch (\Exception $e) {
            $app = $this->getApp();
            $app['logger']->error(ExceptionFormatter::format($e));
        }

        $presenter = new BatchCustomerPresenter($existing, $deleted, $pending);
        return $presenter->toArray();
    }

    public function getBulkInvoices()
    {
        $json = file_get_contents('php://input');
        $json = json_decode($json);

        $invoices = array();

        try {
            if (!property_exists($json, 'customers')) {
                throw new GenericException('Invalid JSON input');
            }

            $this->load->model('expressly/order');
            $this->load->model('expressly/voucher');
            $this->load->model('account/order');

            foreach ($json->customers as $customer) {
                if (!property_exists($customer, 'email')) {
                    continue;
                }

                $ocOrders = $this->model_expressly_order->getOrderIdByCustomerAndDateRange($customer->email,
                    $customer->from, $customer->to);

                if (empty($ocOrders)) {
                    continue;
                }

                $invoice = new Invoice();
                $invoice->setEmail($customer->email);

                foreach ($ocOrders as $ocOrder) {
                    $total = 0.0;
                    $tax = 0.0;
                    $productCount = 0;
                    $ocProducts = $this->model_account_order->getOrderProducts($ocOrder['order_id']);

                    foreach ($ocProducts as $ocProduct) {
                        $total += (double)$ocProduct['price'];
                        $tax += (double)$ocProduct['tax'];
                        $productCount++;
                    }

                    $order = new Order();
                    $order
                        ->setId($ocOrder['invoice_prefix'] . $ocOrder['invoice_no'])
                        ->setDate(new \DateTime($ocOrder['date_added']))
                        ->setCurrency($ocOrder['currency_code'])
                        ->setTotal($total, $tax)
                        ->setItemCount($productCount);

                    $ocVoucher = $this->model_expressly_voucher->getVoucherCodeByOrderId($ocOrder['order_id']);
                    if (!empty($ocVoucher)) {
                        $order->setCoupon($ocVoucher['code']);
                    }

                    $invoice->addOrder($order);
                }

                $invoices[] = $invoice;
            }
        } catch (\Exception $e) {
            $app = $this->getApp();
            $app['logger']->error(ExceptionFormatter::format($e));
        }

        $presenter = new BatchInvoicePresenter($invoices);

        return $presenter->toArray();
    }
}