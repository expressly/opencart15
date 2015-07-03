<?php

use Admin\CommonController;
use Expressly\Event\MerchantEvent;
use Expressly\Event\PasswordedEvent;
use Expressly\Exception\GenericException;

require_once __DIR__ . '/../../../expressly/includes.php';

class ControllerModuleExpressly extends CommonController
{
    public function index()
    {
        $merchant = $this->getMerchant();
        $this->language->load('module/expressly');
        $token = $this->session->data['token'];

        $this->document->setTitle('Expressly');

        $this->data['breadcrumbs'] = array(
            array(
                'text' => $this->language->get('text_home'),
                'href' => $this->url->link('common/home', 'token=' . $token, 'SSL'),
                'separator' => false
            ),
            array(
                'text' => $this->language->get('text_module'),
                'href' => $this->url->link('extension/module', 'token=' . $token, 'SSL'),
                'separator' => ' :: '
            ),
            array(
                'text' => 'Expressly',
                'href' => $this->url->link('module/expressly', 'token=' . $token, 'SSL'),
                'separator' => ' :: '
            )
        );

        $this->data['action'] = $this->url->link('module/expressly/save', 'token=' . $token, 'SSL');
        $this->data['cancel'] = $this->url->link('extension/module', 'token=' . $token, 'SSL');
        $this->data['token'] = $token;

        $this->data['heading_title'] = 'Expressly';
        $this->data['text_content'] = 'Expressly Content';

        $this->data['expressly_shop_name'] = $merchant->getName();
        $this->data['expressly_image'] = $merchant->getImage();
        $this->data['expressly_terms'] = $merchant->getTerms();
        $this->data['expressly_privacy'] = $merchant->getPolicy();
        $this->data['expressly_destination'] = $merchant->getDestination();
        $this->data['expressly_offer'] = (bool)$merchant->getOffer();
        $this->data['expressly_password'] = $merchant->getPassword();

        $uuid = $merchant->getUuid();
        $password = $merchant->getPassword();
        $this->data['registered'] = empty($uuid) && empty($password) ? false : true;

        $this->data['shop_name'] = $this->language->get('shop_name');
        $this->data['image'] = $this->language->get('image');
        $this->data['image_url'] = $merchant->getImage();
        $this->data['image_comment'] = sprintf(
            $this->language->get('image_comment'),
            $merchant->getImage()
        );
        $this->data['terms'] = $this->language->get('terms');
        $this->data['terms_comment'] = sprintf(
            $this->language->get('terms_comment'),
            $merchant->getTerms()
        );
        $this->data['privacy'] = $this->language->get('privacy');
        $this->data['privacy_comment'] = sprintf(
            $this->language->get('privacy_comment'),
            $merchant->getPolicy()
        );
        //$this->data['destination'] = $this->language->get('destination');
        //$this->data['offer'] = $this->language->get('offer');
        $this->data['password'] = $this->language->get('password');
        $this->data['button_register'] = $this->language->get('button_register');
        $this->data['button_save'] = $this->language->get('button_save');
        $this->data['button_cancel'] = $this->language->get('button_cancel');

        if (isset($this->error['warning'])) {
            $this->data['error_warning'] = $this->error['warning'];
        }

        $this->children = array(
            'common/footer',
            'common/header'
        );

        $this->template = 'module/expressly.tpl';

        $this->response->setOutput($this->render());
    }

    public function save()
    {
        $app = $this->getApp();

        try {
            if ($this->request->server['REQUEST_METHOD'] == 'POST' && $this->validate()) {
                $this->language->load('module/expressly');

                $provider = $app['merchant.provider'];
                $merchant = $provider->getMerchant();

                $merchant
                    ->setImage($this->request->post['expressly_image'])
                    ->setTerms($this->request->post['expressly_terms'])
                    ->setPolicy($this->request->post['expressly_privacy']);
//                    ->setDestination($this->request->post['expressly_destination'])
//                    ->setOffer($this->request->post['expressly_offer']);

                $provider->setMerchant($merchant);
                $dispatcher = $this->getDispatcher();

                $uuid = $merchant->getUuid();
                $password = $merchant->getPassword();
                $event = new PasswordedEvent($merchant);

                if (empty($uuid) && empty($password)) {
                    $event = new MerchantEvent($merchant);
                    $dispatcher->dispatch('merchant.register', $event);
                } else {
                    $dispatcher->dispatch('merchant.update', $event);
                }

                if (!$event->isSuccessful()) {
                    throw new \Exception(self::processError($event));
                }

                if (empty($uuid) && empty($password)) {
                    $content = $event->getContent();

                    $merchant
                        ->setUuid($content['merchantUuid'])
                        ->setPassword($content['secretKey']);

                    $this->session->data['success'] = $this->language->get('text_success_register');
                } else {
                    $this->session->data['success'] = $this->language->get('text_success_update');
                }

                $provider->setMerchant($merchant);

                $this->cache->delete('expressly');
            }
        } catch (Buzz\Exception\RequestException $e) {
            $app['logger']->error(Expressly\Exception\ExceptionFormatter::format($e));
            $this->data['error_warning'] = 'We had trouble talking to the server. The server could be down; please contact expressly.';
        } catch (\Exception $e) {
            $app['logger']->error(Expressly\Exception\ExceptionFormatter::format($e));
            $this->data['error_warning'] = (string)$e->getMessage();
        }

        $this->index();
    }

    protected function validate()
    {
        if (!$this->user->hasPermission('modify', 'module/expressly')) {
            $this->data['error_warning'] = $this->language->get('error_permission');
        }

        return !$this->error ? true : false;
    }

    public function install()
    {
        $this->load->model('setting/setting');
        $url = $this->config->get('config_url');
        if (empty($url)) {
            $url = $_SERVER['REQUEST_METHOD'] . $_SERVER['REMOTE_ADDR'];
        }
        $this->model_setting_setting->editSetting(
            'expressly_preferences',
            array(
                'uuid' => '',
                'image' => sprintf('%simage/%s', $url, $this->config->get('config_logo')),
                'terms' => $url . 'index.php?route=information/information&information_id=5',
                'policy' => $url . 'index.php?route=information/information&information_id=3',
                'host' => $url,
                'destination' => '/',
                'offer' => (int)true,
                'password' => '',
                'path' => '?route=expressly/dispatcher/index&query='
            )
        );

        $this->index();
    }

    public function uninstall()
    {
        $this->load->model('setting/setting');
        $this->model_setting_setting->deleteSetting('expressly_preferences');

        $app = $this->getApp();
        $merchant = $this->getMerchant();

        try {
            $event = new PasswordedEvent($merchant);
            $dispatcher = $this->getDispatcher();
            $dispatcher->dispatch('merchant.delete', $event);

            if (!$event->isSuccessful()) {
                throw new GenericException('Failed to uninstall');
            }
        } catch (\Exception $e) {
            $app['logger']->error(Expressly\Exception\ExceptionFormatter::format($e));
        }
    }

    public function register()
    {
        $app = $this->getApp();

        try {
            $dispatcher = $this->getDispatcher();
            $provider = $app['merchant.provider'];
            $merchant = $provider->getMerchant(true);

            $uuid = $merchant->getUuid();
            $password = $merchant->getPassword();
            if (!empty($uuid) && !empty($password)) {
                throw new \Exception("{$merchant->getHost()} is already registered.");
            }

            $event = new MerchantEvent($merchant);
            $dispatcher->dispatch('merchant.register', $event);

            if (!$event->isSuccessful()) {
                throw new \Exception(self::processError($event));
            }

            $content = $event->getContent();
            $merchant
                ->setUuid($content['merchantUuid'])
                ->setPassword($content['secretKey']);

            $provider->setMerchant($merchant);
        } catch (Buzz\Exception\RequestException $e) {
            $app['logger']->error(Expressly\Exception\ExceptionFormatter::format($e));
            $this->data['error_warning'] = 'We had trouble talking to the server. Please contact expressly.';
        } catch (\Exception $e) {
            $app['logger']->error(Expressly\Exception\ExceptionFormatter::format($e));
            $this->data['error_warning'] = $e->getMessage();
        }

        $this->index();
    }
}