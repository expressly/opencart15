<?php

namespace Admin\Expressly;

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
        $preferences = $this->model_setting_setting->getSetting('EXPRESSLY_PREFERENCES');

        $merchant = new Merchant();

        // Assumption to wether array is completely full, or not at all is valid
        if (!empty($preferences)) {
            $merchant
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
        $this->load->model('setting/setting');
        $this->model_setting_setting->editSetting(
            'expressly_preferences',
            array(
                'name' => $merchant->getName(),
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

    public function getMerchant($update = false)
    {
        if (!$this->merchant instanceof Merchant || $update) {
            $this->updateMerchant();
        }

        return $this->merchant;
    }
}

