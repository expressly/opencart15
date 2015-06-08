<?php

namespace Admin;

class CommonController extends \AbstractCommonController
{
    protected function setOverrides($app, $registry)
    {
        $app['merchant.provider'] = $app->share(function () use ($registry) {
            return new MerchantProvider($registry);
        });
    }
}