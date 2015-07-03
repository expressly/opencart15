<?php

namespace Catalog;

use Expressly\Entity\Merchant;

class MerchantProvider extends \AbstractMerchantProvider
{
    public function setMerchant(Merchant $merchant)
    {
        $this->merchant = $merchant;

        return $this;
    }
}

