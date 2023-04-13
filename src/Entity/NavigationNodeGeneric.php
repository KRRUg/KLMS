<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class NavigationNodeGeneric extends NavigationNode
{
    #[ORM\Column(type: 'string', length: 50, nullable: false)]
    private ?string $path;

    public function __construct(string $path = '/')
    {
        parent::__construct();
        $this->path = $path;
    }

    public function getPath(): ?string
    {
        return $this->path;
    }

    public function setPath(string $path): self
    {
        $this->path = $path;

        return $this;
    }

    public function getType(): ?string
    {
        return self::NAV_NODE_TYPE_PATH;
    }

    public function getTargetId(): ?int
    {
        return null;
    }
}