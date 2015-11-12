<?php

namespace Admin;

use Expressly\Entity\Merchant;

class MerchantProvider extends \AbstractMerchantProvider
{
    public function setMerchant(Merchant $merchant)
    {
        $this->load->model('setting/setting');
        $this->model_setting_setting->editSetting(
            'expressly_preferences',
            array(
                static::APIKEY => $merchant->getApiKey(),
                static::HOST => $merchant->getHost(),
                static::PATH => $merchant->getPath()
            )
        );

        $this->merchant = $merchant;

        return $this;
    }
}

