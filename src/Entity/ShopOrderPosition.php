<?php

namespace App\Entity;

use App\Repository\ShopOrderPositionRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Table(name: "shop_order_position")]
#[ORM\Entity(repositoryClass: ShopOrderPositionRepository::class)]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'type', type: 'string', length: 25)]
#[ORM\DiscriminatorMap(['ticket' => ShopOrderPositionTicket::class, 'addon' => ShopOrderPositionAddon::class])]
abstract class ShopOrderPosition
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'shopOrderPositions')]
    #[ORM\JoinColumn(name: 'order_id', nullable: false)]
    private ?ShopOrder $order = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrder(): ?ShopOrder
    {
        return $this->order;
    }

    public function setOrder(?ShopOrder $order): static
    {
        $this->order = $order;

        return $this;
    }
}
