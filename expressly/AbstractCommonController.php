<?php

use Expressly\Client;
use Expressly\Entity\MerchantType;

abstract class AbstractCommonController extends Controller
{
    private $app;
    private $dispatcher;

    public function __construct($registry)
    {
        parent::__construct($registry);

        $client = new Client(MerchantType::OPENCART_1);
        $app = $client->getApp();

        $this->setOverrides($app, $registry);

        $this->app = $app;
        $this->dispatcher = $this->app['dispatcher'];
    }

    protected abstract function setOverrides($app, $registry);

    public function getApp()
    {
        return $this->app;
    }

    public function getDispatcher()
    {
        return $this->dispatcher;
    }

    public function getMerchant()
    {
        return $this->app['merchant.provider']->getMerchant();
    }
}