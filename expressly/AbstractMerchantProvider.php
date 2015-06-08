<?php

use Expressly\Entity\Merchant;
use Expressly\Provider\MerchantProviderInterface;

abstract class AbstractMerchantProvider extends \Controller implements MerchantProviderInterface
{
    protected $merchant;

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

        // Assumption to whether array is completely full, or not at all is valid
        if (!empty($preferences)) {
            $merchant
                ->setName($this->config->get('config_title'))
                ->setUuid($preferences['uuid'])
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

    public abstract function setMerchant(Merchant $merchant);

    public function getMerchant($update = false)
    {
        if (!$this->merchant instanceof Merchant || $update) {
            $this->updateMerchant();
        }

        return $this->merchant;
    }
}

