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

        $this->updateMerchant();
    }

    private function updateMerchant()
    {
        $this->load->model('setting/setting');
        $preferences = $this->model_setting_setting->getSetting('expressly_preferences');

        $merchant = new Merchant();

        // Assumption to wether array is completely full, or not at all is valid
        if (!empty($preferences)) {
            $merchant
                ->setName($preferences['name'])
                ->setImage($preferences['image'])
                ->setTerms($preferences['terms'])
                ->setPolicy($preferences['policy'])
                ->setDestination($preferences['destination'])
                ->setHost($preferences['host'])
                ->setOffer($preferences['offer'])
                ->setPassword($preferences['password'])
                ->setPath($preferences['path']);
        }

        $this->merchant = $merchant;
    }

    public function setMerchant(Merchant $merchant)
    {
        $this->merchant = $merchant;

        return $this;
    }

    public function getMerchant($update = false)
    {
        if (!$this->merchant instanceof Merchant || $update) {
            $this->updateMerchant();
        }

        return $this->merchant;
    }
}

