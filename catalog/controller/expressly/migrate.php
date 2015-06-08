<?php

use Catalog\CommonController;
use Expressly\Event\CustomerMigrateEvent;

require_once __DIR__ . '/../../../expressly/includes.php';

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

        try {
            $event = new CustomerMigrateEvent($this->getMerchant(), $uuid);
            $dispatcher->dispatch('customer.migrate.complete', $event);
            $response = $event->getResponse();
            $json = $event->getContent();

            if (!$response->isSuccessful()) {
                $this->redirect('/');
            }

            $this->customer->logout();
            $this->cart->clear();

            $email = $json['data']['email'];
            $this->load->model('account/customer');

            if ($this->model_account_customer->getTotalCustomersByEmail($email) == 0) {
                $this->load->model('account/address');
                $this->load->model('expressly/country');
                $this->load->model('expressly/zone');

                $password = md5('xly' . microtime());
                $customer = $json['data']['customerData'];
                $phone = !empty($customer['phones']) ? $customer['phones'][0]['number'] : '';

                $shippingAddress = $customer['addresses'][$customer['shippingAddress']];
                $code = $app['country_code.provider']->getIso3($shippingAddress['country']);
                $country = $this->model_expressly_country->getByIso3($code);
                $zone = array('zone_id' => 0);
                if (!empty($country) && !empty($shippingAddress['stateProvince'])) {
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
                 * Adding the billing address first, as the default is set whenever
                 * a new address is added.
                 *
                 * Email is dispatched after core function completes
                 */
                $this->model_account_customer->addCustomer(array(
                    'firstname' => $customer['firstName'],
                    'lastname' => $customer['lastName'],
                    'email' => $email,
                    'password' => $password,
                    'telephone' => $phone,
                    'fax' => '',
                    'newsletter' => true,
                    'company' => !empty($shippingAddress['company']) ? $shippingAddress['company'] : '',
                    'company_id' => '',
                    'tax_id' => !empty($customer['taxNumber']) ? $customer['taxNumber'] : '',
                    'address_1' => $shippingAddress['address1'],
                    'address_2' => !empty($shippingAddress['address2']) ? $shippingAddress['address2'] : '',
                    'city' => $shippingAddress['city'],
                    'postcode' => $shippingAddress['zip'],
                    'country_id' => !empty($country) ? $country['country_id'] : '',
                    'zone_id' => $zone['zone_id']
                ));

                // Log user in
                $this->customer->login($email, $password, true);

                /*
                 * Can only add extra addresses to a logged in user (stupid right?)
                 * Default billing address added, but not connected
                 */
                $billingAddress = $customer['addresses'][$customer['billingAddress']];
                $code = $app['country_code.provider']->getIso3($billingAddress['country']);
                $country = $this->model_expressly_country->getByIso3($code);
                $zone = array('zone_id' => 0);
                if (!empty($country) && !empty($billingAddress['stateProvince'])) {
                    $zone = $this->model_expressly_zone->getByNameOrCodeAndCountry($billingAddress['stateProvince'],
                        $country['country_id']);
                }

                $this->model_account_address->addAddress(array(
                    'firstname' => $customer['firstName'],
                    'lastname' => $customer['lastName'],
                    'company' => !empty($billingAddress['company']) ? $billingAddress['company'] : '',
                    'company_id' => '',
                    'tax_id' => !empty($customer['taxNumber']) ? $customer['taxNumber'] : '',
                    'address_1' => $billingAddress['address1'],
                    'address_2' => !empty($billingAddress['address2']) ? $billingAddress['address2'] : '',
                    'city' => $billingAddress['city'],
                    'postcode' => $billingAddress['zip'],
                    'country_id' => !empty($country) ? $country['country_id'] : '',
                    'zone_id' => $zone['zone_id']
                ));
            }

            // Add item to cart
            if (!empty($json['cart']['productId'])) {
                $this->cart->add($json['cart']['productId'], 1, '');
            }

            if (!empty($json['cart']['couponCode'])) {
                $this->session->data['coupon'] = $json['cart']['couponCode'];
            }

            $dispatcher->dispatch('customer.migrate.success', $event);
        } catch (\Exception $e) {
            $app['logger']->addError((string)$e);
        }

        $this->redirect('/');
    }
}