<?php

namespace Catalog\Expressly;

use Expressly\Entity\Merchant;
use Expressly\Provider\MerchantProviderInterface;

class MerchantProvider extends \Controller implements MerchantProviderInterface
{
    private $merchant;

    public function __construct($registry)
    {
        parent::__construct($registry);

        $this->load->model('setting/setting');
        $preferences = $this->model_setting_setting->getSetting('EXPRESSLY_PREFERENCES');

        $merchant = new Merchant();

        // Assumption to wether array is completely full, or not at all is valid
        if (!empty($preferences)) {
            $merchant
                ->setDestination($preferences['DESTINATION'])
                ->setHost($preferences['HOST'])
                ->setOffer($preferences['OFFER'])
                ->setPassword($preferences['PASSWORD'])
                ->setPath($preferences['PATH']);
        }

        $this->merchant = $merchant;
    }

    public function setMerchant(Merchant $merchant)
    {
        $this->merchant = $merchant;

        return $this;
    }

    public function getMerchant()
    {
        return $this->merchant;
    }
}

