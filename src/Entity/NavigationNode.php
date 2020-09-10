<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\NavigationNodeRepository")
 * @ORM\Table(name="navigation_node")
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
     * @ORM\JoinColumn(nullable=false)
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

    public function __construct(Navigation $navigation)
    {
        $this->navigation = $navigation;
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
    public function __construct(Navigation $navigation)
    {
        parent::__construct($navigation);
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
        return null;
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

    public function __construct(Navigation $navigation, Content $content)
    {
        parent::__construct($navigation);
        $this->content = $content;
    }

    /**
     * @return Content
     */
    public function getContent(): Content
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
        return 'content';
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
    public function __construct(Navigation $navigation)
    {
        parent::__construct($navigation);
    }

    public function getPath(): ?string
    {
        return null;
    }

    public function getType(): ?string
    {
        return 'empty';
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

    public function __construct(Navigation $navigation)
    {
        parent::__construct($navigation);
        $this->path = "/";
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
        return 'path';
    }

    public function getTargetId(): ?int
    {
        return null;
    }
}
