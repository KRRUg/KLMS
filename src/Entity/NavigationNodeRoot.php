<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class NavigationNodeRoot extends NavigationNode
{
    public function __construct()
    {
        parent::__construct();
        $this->setName('KLMS');
    }

    public function __toString(): string
    {
        return '';
    }

    public function getType(): ?string
    {
        return self::NAV_NODE_TYPE_ROOT;
    }

    public function getTargetId(): ?int
    {
        return null;
    }
}