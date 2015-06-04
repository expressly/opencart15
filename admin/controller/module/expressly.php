<?php

use Expressly\Entity\Merchant;
use Expressly\Event\MerchantEvent;

require __DIR__ . '/../expressly/common.php';

class ControllerModuleExpressly extends CommonController
{
    public function index()
    {
        $app = $this->getApp();
        $merchant = $app['merchant.provider']->getMerchant();
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

        $this->data['shop_name'] = $this->language->get('shop_name');
        $this->data['image'] = $this->language->get('image');
        $this->data['terms'] = $this->language->get('terms');
        $this->data['privacy'] = $this->language->get('privacy');
        $this->data['destination'] = $this->language->get('destination');
        $this->data['offer'] = $this->language->get('offer');
        $this->data['password'] = $this->language->get('password');
        $this->data['button_save'] = $this->language->get('button_save');
        $this->data['button_cancel'] = $this->language->get('button_cancel');

        $this->data['error_warning'] = '';
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
        if ($this->request->server['REQUEST_METHOD'] == 'POST' && $this->validate()) {
            $this->language->load('module/expressly');

            $app = $this->getApp();
            $provider = $app['merchant.provider'];
            $merchant = $provider->getMerchant();

            $merchant
                ->setName($this->request->post['expressly_shop_name'])
                ->setImage($this->request->post['expressly_image'])
                ->setTerms($this->request->post['expressly_terms'])
                ->setPolicy($this->request->post['expressly_privacy'])
                ->setDestination($this->request->post['expressly_destination'])
                ->setOffer($this->request->post['expressly_offer'])
                ->setPassword($this->request->post['expressly_password']);

            $provider->setMerchant($merchant);
            $dispatcher = $this->getDispatcher();
            $event = new MerchantEvent($merchant);
            $dispatcher->dispatch('merchant.update', $event);

            $this->session->data['success'] = $this->language->get('text_success');
            $this->cache->delete('expressly');
        }

        $this->redirect($this->url->link('extension/module', 'token=' . $this->session->data['token'], 'SSL'));
    }

    protected function validate()
    {
        if (!$this->user->hasPermission('modify', 'module/expressly')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }

        return !$this->error ? true : false;
    }

    public function install()
    {
        $this->load->model('setting/setting');
        $url = $this->config->get('config_url');
        $this->model_setting_setting->editSetting(
            'expressly_preferences',
            array(
                'name' => $this->config->get('config_title'),
                'image' => sprintf('%simage/%s', $url, $this->config->get('config_logo')),
                'terms' => $url . 'index.php?route=information/information&information_id=5',
                'policy' => $url . 'index.php?route=information/information&information_id=3',
                'host' => $url,
                'destination' => '/',
                'offer' => (int)true,
                'password' => Merchant::createPassword(),
                'path' => '?route=expressly/dispatcher/index&query='
            )
        );

        try {
            $app = $this->getApp();
            $dispatcher = $this->getDispatcher();
            $merchant = $app['merchant.provider']->getMerchant(true);
            $dispatcher->dispatch('merchant.register', new MerchantEvent($merchant));
            $dispatcher->dispatch('merchant.password.save', new MerchantEvent($merchant));
        } catch (\Exception $e) {
            // TODO: Log
        }
    }
}