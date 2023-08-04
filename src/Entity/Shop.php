<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Shopware\AppBundle\Entity\AbstractShop;

#[ORM\Entity]
class Shop extends AbstractShop
{
    public function toArray(): array
    {
        return [
            'ShopId' => $this->getShopId(),
            'ShopUrl' => $this->getShopUrl(),
            'ShopClientId' => $this->getShopClientId(),
            'ShopClientSecret' => $this->getShopClientSecret(),
        ];
    }
}