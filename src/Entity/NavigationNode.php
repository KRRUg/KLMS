<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

// TODO one subtype for each type?

/**
 * @ORM\Entity(repositoryClass="App\Repository\NavigationNodeRepository")
 */
class NavigationNode
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
     * @ORM\ManyToOne(targetEntity="App\Entity\NavigationNode", inversedBy="childNodes")
     */
    private $parent;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\NavigationNode", mappedBy="parent")
     */
    private $childNodes;

    /**
     * @ORM\Column(type="string", length=50, nullable=true, columnDefinition="ENUM('summary', 'content', 'seatmap')")
     */
    private $type;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $targetId;

    public function __construct()
    {
        $this->childNodes = new ArrayCollection();
    }

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

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getTargetId(): ?int
    {
        return $this->targetId;
    }

    public function setTargetId(int $targetId): self
    {
        $this->targetId = $targetId;

        return $this;
    }
}
