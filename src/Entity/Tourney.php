<?php

namespace App\Entity;

use App\Repository\TourneyRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TourneyRepository::class)]
class Tourney
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $description = null;

    #[ORM\Column]
    private ?int $teamsize = null;

    #[ORM\Column]
    private ?bool $hidden = null;

    #[ORM\OneToMany(mappedBy: 'tourney', targetEntity: TourneyTeam::class, orphanRemoval: true)]
    private Collection $teams;

    #[ORM\Column(length: 25)]
    private ?string $mode = null;

    #[ORM\OneToMany(mappedBy: 'tourney', targetEntity: TourneyGame::class, orphanRemoval: true)]
    private Collection $games;

    #[ORM\Column(length: 1)]
    private ?string $result_type = null;

    public function __construct()
    {
        $this->teams = new ArrayCollection();
        $this->games = new ArrayCollection();
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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getTeamsize(): ?int
    {
        return $this->teamsize;
    }

    public function setTeamsize(int $teamsize): self
    {
        $this->teamsize = $teamsize;

        return $this;
    }

    public function isHidden(): ?bool
    {
        return $this->hidden;
    }

    public function setHidden(bool $hidden): self
    {
        $this->hidden = $hidden;

        return $this;
    }

    /**
     * @return Collection<int, TourneyTeam>
     */
    public function getTeams(): Collection
    {
        return $this->teams;
    }

    public function addTeam(TourneyTeam $team): self
    {
        if (!$this->teams->contains($team)) {
            $this->teams->add($team);
            $team->setTourney($this);
        }

        return $this;
    }

    public function removeTeam(TourneyTeam $team): self
    {
        if ($this->teams->removeElement($team)) {
            // set the owning side to null (unless already changed)
            if ($team->getTourney() === $this) {
                $team->setTourney(null);
            }
        }

        return $this;
    }

    public function getMode(): ?string
    {
        return $this->mode;
    }

    public function setMode(string $mode): self
    {
        $this->mode = $mode;

        return $this;
    }

    /**
     * @return Collection<int, TourneyGame>
     */
    public function getGames(): Collection
    {
        return $this->games;
    }

    public function addGame(TourneyGame $game): self
    {
        if (!$this->games->contains($game)) {
            $this->games->add($game);
            $game->setTourney($this);
        }

        return $this;
    }

    public function removeGame(TourneyGame $game): self
    {
        if ($this->games->removeElement($game)) {
            // set the owning side to null (unless already changed)
            if ($game->getTourney() === $this) {
                $game->setTourney(null);
            }
        }

        return $this;
    }

    public function getResultType(): ?string
    {
        return $this->result_type;
    }

    public function setResultType(string $result_type): self
    {
        $this->result_type = $result_type;

        return $this;
    }
}
