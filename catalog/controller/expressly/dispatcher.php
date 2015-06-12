<?php

use Catalog\CommonController;
use Expressly\Entity\Address;
use Expressly\Entity\Customer;
use Expressly\Entity\Email;
use Expressly\Entity\Phone;
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

        if (preg_match("/^\/?expressly\/api\/ping\/?$/", $query)) {
            $this->ping();

            return;
        }

        if (preg_match("/^\/?expressly\/api\/user\/([\w-\.]+@[\w-\.]+)\/?$/", $query, $matches)) {
            $email = array_pop($matches);
            $this->retrieveUserByEmail($email);

            return;
        }

        if (preg_match("/^\/?expressly\/api\/([\w-]+)\/?$/", $query, $matches)) {
            $key = array_pop($matches);
            $this->redirect($this->url->link('expressly/migrate/popup', "uuid={$key}", 'SSL'));

            return;
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
            $this->response->setOutput(json_encode(array()));

            return;
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

        $this->response->setOutput(json_encode($response->toArray()));
    }
}