<?php

use Catalog\CommonController;
use Expressly\Entity\Address;
use Expressly\Entity\Customer;
use Expressly\Entity\Email;
use Expressly\Entity\Invoice;
use Expressly\Entity\Order;
use Expressly\Entity\Phone;
use Expressly\Exception\ExceptionFormatter;
use Expressly\Presenter\BatchCustomerPresenter;
use Expressly\Presenter\BatchInvoicePresenter;
use Expressly\Presenter\CustomerMigratePresenter;
use Expressly\Presenter\PingPresenter;

require_once __DIR__ . '/../../../expressly/includes.php';

class ControllerExpresslyDispatcher extends CommonController
{
    public function index()
    {
        if (empty($this->request->get['query'])) {
            $this->redirect('/');
        }

        $query = $this->request->get['query'];

        switch ($this->request->server['REQUEST_METHOD']) {
            case 'GET':
                if (preg_match("/^\/?expressly\/api\/ping\/?$/", $query)) {
                    $this->ping();

                    return;
                }

                if (preg_match("/^\/?expressly\/api\/user\/([\w-\.]+@[\w-\.]+)\/?$/", $query, $matches)) {
                    $email = array_pop($matches);
                    $data = $this->retrieveUserByEmail($email);
                    $this->response->setOutput(json_encode($data));

                    return;
                }

                if (preg_match("/^\/?expressly\/api\/([\w-]+)\/?$/", $query, $matches)) {
                    $key = array_pop($matches);
                    $this->redirect($this->url->link('expressly/migrate/popup', "uuid={$key}", 'SSL'));

                    return;
                }
                break;
            case 'POST':
                if (preg_match("/^\/?expressly\/api\/batch\/invoice\/?$/", $query, $matches)) {
                    $data = $this->getBulkInvoices();
                    $this->response->setOutput(json_encode($data));

                    return;
                }

                if (preg_match("/^\/?expressly\/api\/batch\/customer\/?$/", $query, $matches)) {
                    $data = $this->getBulkCustomers();
                    $this->response->setOutput(json_encode($data));

                    return;
                }
                break;
        }

        $this->redirect('/');
    }

    private function ping()
    {
        $presenter = new PingPresenter();
        $this->response->setOutput(json_encode($presenter->toArray()));
    }

    private function retrieveUserByEmail($emailAddress)
    {
        $this->load->model('account/customer');
        $ocCustomer = $this->model_account_customer->getCustomerByEmail($emailAddress);

        if (empty($ocCustomer)) {
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

        $customers = array();

        try {
            $this->load->model('account/customer');

            foreach ($json->emails as $customer) {
                $ocCustomer = $this->model_account_customer->getCustomerByEmail($customer);

                if (empty($ocCustomer)) {
                    continue;
                }
                if (!$ocCustomer['status']) {
                    $customers['deleted'][] = $customer;
                    continue;
                }
                if (!$ocCustomer['approved']) {
                    $customers['pending'][] = $customer;
                    continue;
                }

                $customers['existing'][] = $customer;
            }
        } catch (\Exception $e) {
            $app = $this->getApp();
            $app['logger']->error(ExceptionFormatter::format($e));
        }

        // TODO: use static names defined once for array indexes
        $presenter = new BatchCustomerPresenter($customers);

        return $presenter->toArray();
    }

    public function getBulkInvoices()
    {
        $json = file_get_contents('php://input');
        $json = json_decode($json);

        $invoices = array();

        try {
            $this->load->model('expressly/order');
            $this->load->model('expressly/voucher');
            $this->load->model('account/order');

            foreach ($json->customers as $customer) {
                $ocOrders = $this->model_expressly_order->getOrderIdByCustomerAndDateRange($customer->email, $customer->from, $customer->to);

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