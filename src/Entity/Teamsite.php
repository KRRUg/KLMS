<?php

namespace App\Entity;

use App\Entity\Traits\EntityHistoryTrait;
use App\Entity\Traits\HistoryAwareEntity;
use App\Repository\TeamsiteRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TeamsiteRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Teamsite implements HistoryAwareEntity
{
    use EntityHistoryTrait;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: 'text')]
    private ?string $description = null;

    #[ORM\OneToMany(targetEntity: TeamsiteCategory::class, mappedBy: 'teamsite', orphanRemoval: true, cascade: ['all'])]
    #[ORM\OrderBy(['ord' => 'ASC'])]
    private Collection $categories;

    public function __construct()
    {
        $this->categories = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription()
    {
        return $this->description;
    }

    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return Collection|TeamsiteCategory[]
     */
    public function getCategories(): Collection
    {
        return $this->categories;
    }

    public function addCategory(TeamsiteCategory $category): self
    {
        if (!$this->categories->contains($category)) {
            $this->categories[] = $category;
            $category->setTeamsite($this);
        }

        return $this;
    }

    public function clearCategories(): self
    {
        foreach ($this->categories as $cat) {
            if ($cat->getTeamsite() === $this) {
                $cat->setTeamsite(null);
            }
        }
        $this->categories->clear();

        return $this;
    }

    public function removeCategory(TeamsiteCategory $category): self
    {
        if ($this->categories->removeElement($category)) {
            // set the owning side to null (unless already changed)
            if ($category->getTeamsite() === $this) {
                $category->setTeamsite(null);
            }
        }

        return $this;
    }
}
