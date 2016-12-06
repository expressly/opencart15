<?php

use Admin\CommonController;
use Expressly\Event\MerchantEvent;
use Expressly\Event\PasswordedEvent;
use Expressly\Exception\GenericException;
use Expressly\Exception\InvalidAPIKeyException;
use Expressly\Subscriber\MerchantSubscriber;

require_once __DIR__ . '/../../../expressly/includes.php';

class ControllerModuleExpressly extends CommonController
{
    public function index()
    {
        $merchant = $this->getMerchant();
        $this->language->load('module/expressly');
        $token = $this->session->data['token'];

        $this->document->setTitle('Expressly');

        $this->load->model('setting/setting');

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
            $this->model_setting_setting->editSetting('expressly', $this->request->post);

            $this->session->data['success'] = $this->language->get('text_success');

            $this->redirect($this->url->link('extension/module', 'token=' . $this->session->data['token'], 'SSL'));
        }

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

        // Language
        $this->data['text_enabled'] = $this->language->get('text_enabled');
        $this->data['text_disabled'] = $this->language->get('text_disabled');

        $this->data['text_content_top'] = $this->language->get('text_content_top');
        $this->data['text_content_bottom'] = $this->language->get('text_content_bottom');
        $this->data['text_column_left'] = $this->language->get('text_column_left');
        $this->data['text_column_right'] = $this->language->get('text_column_right');

        $this->data['entry_layout'] = $this->language->get('entry_layout');
        $this->data['entry_position'] = $this->language->get('entry_position');
        $this->data['entry_status'] = $this->language->get('entry_status');
        $this->data['entry_sort_order'] = $this->language->get('entry_sort_order');

        $this->data['button_save'] = $this->language->get('button_save');
        $this->data['button_cancel'] = $this->language->get('button_cancel');
        $this->data['button_add_module'] = $this->language->get('button_add_module');
        $this->data['button_remove'] = $this->language->get('button_remove');

        $this->data['text_enabled'] = $this->language->get('text_enabled');
        $this->data['text_disabled'] = $this->language->get('text_disabled');
        $this->data['text_content_top'] = $this->language->get('text_content_top');
        $this->data['text_content_bottom'] = $this->language->get('text_content_bottom');
        $this->data['text_column_left'] = $this->language->get('text_column_left');
        $this->data['text_column_right'] = $this->language->get('text_column_right');

        $this->data['entry_layout'] = $this->language->get('entry_layout');
        $this->data['entry_position'] = $this->language->get('entry_position');
        $this->data['entry_status'] = $this->language->get('entry_status');
        $this->data['entry_sort_order'] = $this->language->get('entry_sort_order');

        $this->data['expressly_apikey'] = $merchant->getApiKey();

        $this->data['apikey'] = $this->language->get('apikey');
        $this->data['apikey_comment'] = $this->language->get('apikey_comment');

        $this->data['button_save'] = $this->language->get('button_save');
        $this->data['button_cancel'] = $this->language->get('button_cancel');

        $this->load->model('design/layout');

        $this->data['layouts'] = $this->model_design_layout->getLayouts();

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
                $apiKey = $this->request->post['expressly_apikey'];
                $merchant->setApiKey($apiKey);
                $provider->setMerchant($merchant);

                $dispatcher = $this->getDispatcher();
                $event = new PasswordedEvent($merchant);

                $dispatcher->dispatch(MerchantSubscriber::MERCHANT_REGISTER, $event);

                if (!$event->isSuccessful()) {
                    throw new InvalidAPIKeyException();
                }

                $this->session->data['success'] = $this->language->get('text_success_register');

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
            $url = 'https://' . $_SERVER['REMOTE_ADDR'];
        }
        $this->model_setting_setting->editSetting(
            'expressly_preferences',
            array(
                'apikey' => '',
                'host' => $url,
                'path' => '?route=expressly/dispatcher/index&query='
            )
        );

        $this->index();
    }

    public function uninstall()
    {
        $this->load->model('setting/setting');
        //$this->model_setting_setting->deleteSetting('expressly_preferences');

        $app = $this->getApp();
        $merchant = $this->getMerchant();

        try {
            $event = new PasswordedEvent($merchant);
            $dispatcher = $this->getDispatcher();
            $dispatcher->dispatch(MerchantSubscriber::MERCHANT_DELETE, $event);

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