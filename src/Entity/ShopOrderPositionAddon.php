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

    public function getText(): ?string
    {
        return $this->text;
    }

    public function setText(?string $text): self
    {
        $this->text = $text;

        return $this;
    }

    public function setAddon(ShopAddon $addon): self
    {
        $this->setText($addon->getName());
        $this->setPrice($addon->getPrice());

        return $this;
    }
}