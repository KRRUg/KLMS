<?php

namespace App\Entity;

use App\Repository\NavigationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: NavigationRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[UniqueEntity('name')]
class Navigation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255, unique: true, nullable: false)]
    private ?string $name = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $max_depth = null;

    #[ORM\OneToMany(targetEntity: NavigationNode::class, mappedBy: 'navigation', orphanRemoval: true, cascade: ['all'])]
    #[ORM\OrderBy(['lft' => 'ASC'])]
    private Collection $nodes;

    public function __construct()
    {
        $this->nodes = new ArrayCollection();
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

    public function getMaxDepth(): ?int
    {
        return $this->max_depth;
    }

    public function setMaxDepth(?int $max_depth): self
    {
        $this->max_depth = $max_depth;

        return $this;
    }

    /**
     * @return Collection|NavigationNode[]
     */
    public function getNodes(): Collection
    {
        return $this->nodes;
    }

    public function addNode(NavigationNode $node): self
    {
        if (!$this->nodes->contains($node)) {
            $this->nodes[] = $node;
            $node->setNavigation($this);
        }

        return $this;
    }

    public function removeNode(NavigationNode $node): self
    {
        if ($this->nodes->contains($node)) {
            $this->nodes->removeElement($node);
            // set the owning side to null (unless already changed)
            if ($node->getNavigation() === $this) {
                $node->setNavigation(null);
            }
        }

        return $this;
    }

    public function clearNodes(): self
    {
        foreach ($this->nodes as $node) {
            if ($node->getNavigation() === $this) {
                $node->setNavigation(null);
            }
        }
        $this->nodes->clear();

        return $this;
    }
}
