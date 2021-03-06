<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\NavigationNodeRepository")
 * @ORM\Table(
 *     name="navigation_node",
 *     uniqueConstraints={
 *        @ORM\UniqueConstraint(name="lft_unique", columns={"navigation_id", "lft" }),
 *        @ORM\UniqueConstraint(name="rgt_unique", columns={"navigation_id", "rgt" }),
 * })
 * @ORM\InheritanceType("SINGLE_TABLE")
 * @ORM\DiscriminatorColumn(name="type", type="string", length=25)
 * @ORM\DiscriminatorMap({
 *     "root" = "NavigationNodeRoot",
 *     "empty" = "NavigationNodeEmpty",
 *     "content" = "NavigationNodeContent",
 *     "generic" = "NavigationNodeGeneric"
 * })
 */
abstract class NavigationNode
{
    const NAV_NODE_TYPE_ROOT = "root";
    const NAV_NODE_TYPE_EMPTY = "empty";
    const NAV_NODE_TYPE_PATH = "path";
    const NAV_NODE_TYPE_CONTENT = "content";

    const NAV_NODE_TYPES = [
        self::NAV_NODE_TYPE_ROOT,
        self::NAV_NODE_TYPE_EMPTY,
        self::NAV_NODE_TYPE_PATH,
        self::NAV_NODE_TYPE_CONTENT,
    ];

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Navigation", inversedBy="nodes")
     * @ORM\JoinColumn(name="navigation_id", nullable=false)
     */
    private $navigation;

    /**
     * @ORM\Column(type="integer", nullable=false)
     */
    private $lft;

    /**
     * @ORM\Column(type="integer", nullable=false)
     */
    private $rgt;

    public function __construct()
    {
        $this->name = "";
    }

    public function __toString()
    {
        return $this->getName();
    }

    abstract public function getPath(): ?string;

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

/**
 * @ORM\Entity()
 */
final class NavigationNodeRoot extends NavigationNode
{
    public function __construct()
    {
        parent::__construct();
        $this->setName("KLMS");
    }

    public function __toString()
    {
        return "";
    }

    public function getPath(): ?string
    {
        return null;
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

/**
 * @ORM\Entity()
 */
final class NavigationNodeContent extends NavigationNode
{
    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Content", fetch="LAZY")
     * @ORM\JoinColumn(name="content_id", referencedColumnName="id")
     * @var Content
     */
    private $content;

    public function __construct(Content $content = null)
    {
        parent::__construct();
        $this->content = $content;
    }

    /**
     * @return Content
     */
    public function getContent(): ?Content
    {
        return $this->content;
    }

    /**
     * @param Content $content
     */
    public function setContent(Content $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function getPath(): ?string
    {
        return "/content/" . $this->content->getId();
    }

    public function getType(): ?string
    {
        return self::NAV_NODE_TYPE_CONTENT;
    }

    public function getTargetId(): ?int
    {
        return $this->content->getId();
    }
}

/**
 * @ORM\Entity()
 */
final class NavigationNodeEmpty extends NavigationNode
{
    /**
     * NavigationNodeEmpty constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    public function getPath(): ?string
    {
        return null;
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

/**
 * @ORM\Entity()
 */
final class NavigationNodeGeneric extends NavigationNode
{
    /**
     * @ORM\Column(type="string", length=50, nullable=false)
     * @var string
     */
    private $path;

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
