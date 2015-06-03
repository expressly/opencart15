<?php

use Expressly\Event\CustomerMigrateEvent;

require_once 'common.php';

class ControllerExpresslyMigrate extends CommonController
{
    public function popup()
    {
        if (empty($this->request->get['uuid'])) {
            $this->redirect('/');
        }

        $uuid = $this->request->get['uuid'];
        $dispatcher = $this->getDispatcher();

        $event = new CustomerMigrateEvent($this->getMerchant(), $uuid);
        $dispatcher->dispatch('customer.migrate.start', $event);

        $this->data['response'] = $event->getResponse();

        $this->request->get['route'] = 'common/home';

        $this->children = array(
            'common/home'
        );

        if (file_exists(DIR_TEMPLATE . $this->config->get('config_template') . '/template/module/expressly.tpl')) {
            $this->template = $this->config->get('config_template') . '/template/module/expressly.tpl';
        } else {
            $this->template = 'default/template/module/expressly.tpl';
        }

        $this->document->addScript('catalog/view/javascript/expressly.js');
        $this->response->setOutput($this->render());
    }

    public function complete()
    {
        if (empty($this->request->get['uuid'])) {
            $this->redirect('/');
        }

        $uuid = $this->request->get['uuid'];
        $app = $this->getApp();
        $dispatcher = $this->getDispatcher();

        $event = new CustomerMigrateEvent($this->getMerchant(), $uuid);
        $dispatcher->dispatch('customer.migrate.complete', $event);

        $json = $event->getResponse();

        if (empty($json)) {
            $this->redirect('/');
        }

        try {
            $email = $json['migration']['data']['email'];
            $this->load->model('account/customer');
            if ($this->model_account_customer->getTotalCustomersByEmail($email) > 0) {
                $this->redirect('/');
            }

            $password = md5('xly' . microtime());
            $customer = $json['migration']['data']['customerData'];
            $phone = !empty($customer['phones']) ? $customer['phones'][0]['number'] : '';
            $shippingAddress = $customer['addresses'][$customer['shippingAddress']];
            $code = $app['country_code.provider']->getIso3($shippingAddress['country']);

            $this->load->model('expressly/country');
            $this->load->model('expressly/zone');
            $country = $this->model_expressly_country->getByIso3($code);
            $zone = array(
                'zone_id' => ''
            );
            if (!empty($country)) {
                $zone = $this->model_expressly_zone->getByNameOrCodeAndCountry($shippingAddress['stateProvince'],
                    $country['country_id']);
            }

            /*
             * Limitations:
             * OpenCart can only use 1 address; use default shipping address
             * Payment integration with OpenCart assumed to not use 3d secure,
             * which would explain why this hasn't been an issue for more
             * than a decade.
             * Only 1 phone can be added per user (does not include country code)
             * OpenCart forces first and last name on the address, changes below
             *
             * Email is dispatched after core function completes
             */
            $this->model_account_customer->addCustomer(array(
                'firstname' => $customer['firstName'],
                'lastname' => $customer['lastName'],
                'email' => $email,
                'password' => $password,
                'telephone' => $phone,
                'newsletter' => true,
                'company' => $shippingAddress['company'],
                'company_id' => '',
                'tax_id' => $customer,
                'address_1' => $shippingAddress['address1'],
                'address_2' => $shippingAddress['address2'],
                'city' => $shippingAddress['city'],
                'postcode' => $shippingAddress['zip'],
                'country_id' => !empty($country) ? $country['country_id'] : '',
                'zone_id' => $zone['zone_id']
            ));
            /*
             * Default billing address added, but not connected
             */

            // Log user in
            $this->customer->login($email, $password, true);

            // Add item to cart
            if (!empty($json['cart']['productId'])) {
                $this->cart->add($json['cart']['productId']);
            }

            if (!empty($json['cart']['couponCode'])) {
                $this->session->data['coupon'];
            }

            $dispatcher->dispatch('customer.migrate.success', $event);
        } catch (\Exception $e) {
            // TODO: Log
        }

        $this->redirect('/');
    }
}