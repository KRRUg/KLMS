<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
class ShopOrderPositionAddon extends ShopOrderPosition
{
    #[Assert\NotBlank]
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $text = null;

    /** @var ShopAddon|null The addon. Just used for counting. */
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?ShopAddon $addon = null;

    public function getText(): ?string
    {
        return $this->text;
    }

    public function setText(?string $text): self
    {
        $this->text = $text;

        return $this;
    }

    public function fillWithAddon(ShopAddon $addon): self
    {
        $this->setText($addon->getName());
        $this->setPrice($addon->getPrice());
        $this->setAddon($addon);

        return $this;
    }

    public function getAddon(): ?ShopAddon
    {
        return $this->addon;
    }

    public function setAddon(ShopAddon $addon): self
    {
        $this->addon = $addon;

        return $this;
    }
}