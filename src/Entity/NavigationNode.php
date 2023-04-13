<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Stringable;

#[ORM\Table(name: 'navigation_node')]
#[ORM\UniqueConstraint(name: 'nav_node_lft_unique', columns: ['navigation_id', 'lft'])]
#[ORM\UniqueConstraint(name: 'nav_node_rgt_unique', columns: ['navigation_id', 'rgt'])]
#[ORM\Entity(repositoryClass: 'App\Repository\NavigationNodeRepository')]
#[ORM\InheritanceType('SINGLE_TABLE')]
#[ORM\DiscriminatorColumn(name: 'type', type: 'string', length: 25)]
#[ORM\DiscriminatorMap(['root' => 'NavigationNodeRoot', 'empty' => 'NavigationNodeEmpty', 'generic' => 'NavigationNodeGeneric', 'content' => 'NavigationNodeContent', 'teamsite' => 'NavigationNodeTeamsite'])]
abstract class NavigationNode implements Stringable
{
    public const NAV_NODE_TYPE_ROOT = 'root';
    public const NAV_NODE_TYPE_EMPTY = 'empty';
    public const NAV_NODE_TYPE_PATH = 'path';
    public const NAV_NODE_TYPE_CONTENT = 'content';
    public const NAV_NODE_TYPE_TEAMSITE = 'teamsite';

    public const NAV_NODE_TYPES = [
        self::NAV_NODE_TYPE_ROOT,
        self::NAV_NODE_TYPE_EMPTY,
        self::NAV_NODE_TYPE_PATH,
        self::NAV_NODE_TYPE_CONTENT,
        self::NAV_NODE_TYPE_TEAMSITE,
    ];

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $name = null;

    #[ORM\ManyToOne(inversedBy: 'nodes')]
    #[ORM\JoinColumn(name: 'navigation_id', nullable: false)]
    private ?Navigation $navigation = null;

    #[ORM\Column(type: 'integer', nullable: false)]
    private ?int $lft = null;

    #[ORM\Column(type: 'integer', nullable: false)]
    private ?int $rgt = null;

    public function __construct()
    {
        $this->name = '';
    }

    public function __toString(): string
    {
        return (string) $this->getName();
    }

    public function getPath(): ?string
    {
        $id = $this->getTargetId();

        return is_null($id) ? null : "/{$this->getType()}/{$id}";
    }

    abstract public function getType(): ?string;

    abstract public function getTargetId(): ?int;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getNavigation(): ?Navigation
    {
        return $this->navigation;
    }

    public function setNavigation(?Navigation $navigation): self
    {
        $this->navigation = $navigation;

        return $this;
    }

    public function getLft(): ?int
    {
        return $this->lft;
    }

    public function setLft(int $lft): self
    {
        $this->lft = $lft;

        return $this;
    }

    public function getRgt(): ?int
    {
        return $this->rgt;
    }

    public function setRgt(int $rgt): self
    {
        $this->rgt = $rgt;

        return $this;
    }

    public function setPos($lft, $rgt): self
    {
        $this->lft = $lft;
        $this->rgt = $rgt;

        return $this;
    }
}
