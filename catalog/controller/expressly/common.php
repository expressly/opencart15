<?php

use Catalog\Expressly\MerchantProvider;
use Expressly\Client;

class CommonController extends Controller
{
    private $app;
    private $dispatcher;

    public function __construct($registry)
    {
        parent::__construct($registry);

        require_once __DIR__ . '/vendor/autoload.php';

        $client = new Client();
        $app = $client->getApp();

        $app['merchant.provider'] = $app->share(function () use ($registry) {
            return new MerchantProvider($registry);
        });

        $this->app = $app;
        $this->dispatcher = $this->app['dispatcher'];
    }

    public function getApp()
    {
        return $this->app;
    }

    public function getDispatcher()
    {
        return $this->dispatcher;
    }
}