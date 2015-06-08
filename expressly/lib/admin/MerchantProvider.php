<?php

namespace Admin;

use Expressly\Entity\Merchant;
use Expressly\Provider\MerchantProviderInterface;

class MerchantProvider extends \AbstractMerchantProvider
{
    public function setMerchant(Merchant $merchant)
    {
        $this->load->model('setting/setting');
        $this->model_setting_setting->editSetting(
            'expressly_preferences',
            array(
                'uuid' => $merchant->getUuid(),
                'image' => $merchant->getImage(),
                'terms' => $merchant->getTerms(),
                'policy' => $merchant->getPolicy(),
                'host' => $merchant->getHost(),
                'destination' => $merchant->getDestination(),
                'offer' => (int)$merchant->getOffer(),
                'password' => $merchant->getPassword(),
                'path' => $merchant->getPath()
            )
        );

        $this->merchant = $merchant;

        return $this;
    }
}

