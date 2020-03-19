<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/**
 * @ORM\Entity(repositoryClass="App\Repository\NavigationRepository")
 * @ORM\Table(name="navigation")
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
     * @ORM\Column(type="integer", name="ord")
     */
    private $order;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\NavigationNode", inversedBy="childNodes", fetch="EAGER")
     */
    private $parent;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\NavigationNode", mappedBy="parent", fetch="EAGER")
     * @ORM\OrderBy({"order" = "ASC"})
     */
    private $childNodes;

    public function __construct()
    {
        $this->childNodes = new ArrayCollection();
    }

    public function __toString()
    {
        return $this->getName();
    }

    abstract public function getPath(): ?string;

    abstract public function getType(): ?string;

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

    public function getOrder(): int
    {
        return $this->order;
    }

    public function setOrder($order): self
    {
        $this->order = $order;

        return $this;
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function setParent(?self $parent): self
    {
        $this->parent = $parent;

        return $this;
    }

    /**
     * @return Collection|self[]
     */
    public function getChildNodes(): Collection
    {
        return $this->childNodes;
    }

    public function addChildNode(self $childNode): self
    {
        if (!$this->childNodes->contains($childNode)) {
            $this->childNodes[] = $childNode;
            $childNode->setParent($this);
        }

        return $this;
    }

    public function removeChildNode(self $childNode): self
    {
        if ($this->childNodes->contains($childNode)) {
            $this->childNodes->removeElement($childNode);
            // set the owning side to null (unless already changed)
            if ($childNode->getParent() === $this) {
                $childNode->setParent(null);
            }
        }

        return $this;
    }
}

/**
 * @ORM\Entity()
 */
class NavigationNodeRoot extends NavigationNode
{
    public function __construct()
    {
        parent::__construct();
        $this->setParent(null);
        $this->setName("KLMS");
        $this->setOrder(0);
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
}

/**
 * @ORM\Entity()
 */
class NavigationNodeContent extends NavigationNode
{
    /**
     * @ORM\OneToOne(targetEntity="App\Entity\Content", fetch="LAZY")
     * @ORM\JoinColumn(name="content_id", referencedColumnName="id")
     * @var Content
     */
    private $content;

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
    public function setContent(Content $content): void
    {
        $this->content = $content;
    }

    public function getPath(): ?string
    {
        return "/content/" . $this->content->getId();
    }

    public function getType(): ?string
    {
        return 'content';
    }
}

/**
 * @ORM\Entity()
 */
class NavigationNodeEmpty extends NavigationNode
{
    public function getPath(): ?string
    {
        return null;
    }

    public function getType(): ?string
    {
        return 'empty';
    }
}

/**
 * @ORM\Entity()
 */
class NavigationNodeGeneric extends NavigationNode
{
    /**
     * @ORM\Column(type="string", length=50, nullable=false)
     * @var string
     */
    private $path;

    public function __construct()
    {
        parent::__construct();
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
}
