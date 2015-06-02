<?php

namespace Admin\Expressly;

use Expressly\Entity\Merchant;
use Expressly\Provider\MerchantProviderInterface;

class MerchantProvider extends Controller implements MerchantProviderInterface
{
    private $merchant;

    public function __construct($registry)
    {
        parent::__construct($registry);

        $this->updateMerchant();
    }

    private function updateMerchant()
    {

    }

    public function setMerchant(Merchant $merchant)
    {

    }

    public function getMerchant()
    {
        return $this->merchant;
    }
}