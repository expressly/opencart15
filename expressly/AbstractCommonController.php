<?php

use Expressly\Client;
use Expressly\Entity\MerchantType;

abstract class AbstractCommonController extends Controller
{
    private $app;
    private $dispatcher;
    private $resolver;

    public function __construct($registry)
    {
        parent::__construct($registry);

        $client = new Client(MerchantType::OPENCART_1);
        $app = $client->getApp();

        $this->setOverrides($app, $registry);

        $this->app = $app;
        $this->dispatcher = $this->app['dispatcher'];
        $this->resolver = $this->app['route.resolver'];
    }


    public static function processError(Symfony\Component\EventDispatcher\Event $event)
    {
        $content = $event->getContent();
        $message = array(
            $content['description']
        );

        $addBulletpoints = function ($key, $title) use ($content, &$message) {
            if (!empty($content[$key])) {
                $message[] = '<br>';
                $message[] = $title;
                $message[] = '<ul>';

                foreach ($content[$key] as $point) {
                    $message[] = "<li>{$point}</li>";
                }

                $message[] = '</ul>';
            }
        };

        // TODO: translatable
        $addBulletpoints('causes', 'Possible causes:');
        $addBulletpoints('actions', 'Possible resolutions:');

        return implode('', $message);
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

    public function getResolver()
    {
        return $this->resolver;
    }

    public function getMerchant()
    {
        return $this->app['merchant.provider']->getMerchant();
    }
}