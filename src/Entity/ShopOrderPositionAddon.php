<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class ShopOrderPositionAddon extends ShopOrderPosition
{

    #[ORM\ManyToOne(fetch: 'LAZY')]
    #[ORM\JoinColumn(name: 'addon_id')]
    private ?ShopAddon $addon = null;

    public function getAddon(): ?ShopAddon
    {
        return $this->addon;
    }

    public function setAddon(?ShopAddon $addon): static
    {
        $this->addon = $addon;

        return $this;
    }
}