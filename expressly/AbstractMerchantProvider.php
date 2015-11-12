<?php

use Expressly\Entity\Merchant;
use Expressly\Provider\MerchantProviderInterface;

abstract class AbstractMerchantProvider extends \Controller implements MerchantProviderInterface
{
    protected $merchant;

    const APIKEY = 'apikey';
    const HOST = 'host';
    const PATH = 'path';

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
                ->setApiKey($preferences[static::APIKEY])
                ->setHost($preferences[static::HOST])
                ->setPath($preferences[static::PATH]);
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

