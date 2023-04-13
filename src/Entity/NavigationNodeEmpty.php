<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class NavigationNodeEmpty extends NavigationNode
{
    public function __construct()
    {
        parent::__construct();
    }

    public function getType(): ?string
    {
        return self::NAV_NODE_TYPE_EMPTY;
    }

    public function getTargetId(): ?int
    {
        return null;
    }
}