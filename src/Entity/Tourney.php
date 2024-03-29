<?php

namespace App\Entity;

use App\Entity\Traits\EntityHistoryTrait;
use App\Entity\Traits\HistoryAwareEntity;
use App\Repository\TourneyRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: TourneyRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Tourney implements HistoryAwareEntity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank]
    private ?string $name = null;

    #[ORM\Column(type: 'text')]
    private ?string $description = null;

    #[ORM\Column]
    #[Assert\GreaterThanOrEqual(value: 1, message: 'A team must have at least one member.')]
    private ?int $teamsize = null;

    #[ORM\Column]
    private ?bool $hidden = null;

    #[ORM\Column(enumType: TourneyStage::class)]
    private ?TourneyStage $status;

    #[ORM\Column]
    private ?int $token = null;

    #[ORM\Column(name: 'sort_order', nullable: true)]
    private ?int $order = null;

    #[ORM\OneToMany(mappedBy: 'tourney', targetEntity: TourneyTeam::class, orphanRemoval: true, cascade: ['persist', 'remove'])]
    private Collection $teams;

    #[ORM\Column(type: 'string', length: 2, enumType: TourneyRules::class)]
    private ?TourneyRules $mode = null;

    #[ORM\Column]
    private ?bool $show_points = false;

    #[ORM\OneToMany(mappedBy: 'tourney', targetEntity: TourneyGame::class, orphanRemoval: true, cascade: ['persist', 'remove'])]
    private Collection $games;

    use EntityHistoryTrait;

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

    public function isSinglePlayer(): ?bool
    {
        if ($this->getTeamsize() === null)
            return null;
        return $this->getTeamsize() == 1;
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

    public function getStatus(): ?TourneyStage
    {
        return $this->status;
    }

    public function setStatus(?TourneyStage $status): Tourney
    {
        $this->status = $status;

        return $this;
    }

    public function getToken(): ?int
    {
        return $this->token;
    }

    public function setToken(?int $token): Tourney
    {
        $this->token = $token;

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

    public function getMode(): ?TourneyRules
    {
        return $this->mode;
    }

    public function setMode(?TourneyRules $mode): Tourney
    {
        $this->mode = $mode;

        return $this;
    }

    public function getShowPoints(): ?string
    {
        return $this->show_points;
    }

    public function setShowPoints(string $show_points): self
    {
        $this->show_points = $show_points;

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

    public function isRunning(): bool
    {
        return $this->status == TourneyStage::Running;
    }

    public function getOrder(): ?int
    {
        return $this->order;
    }

    public function setOrder(?int $order): Tourney
    {
        $this->order = $order;

        return $this;
    }

    public function hasTree(): bool
    {
        if (is_null($this->status) || is_null($this->mode))
            return false;
        return $this->status->hasTree() && $this->mode->hasTree();
    }

    public function canHaveTeams(): bool
    {
        if (is_null($this->status) || is_null($this->mode))
            return false;
        return $this->status->canHaveTeams() && $this->mode->canHaveTeams();
    }

    public function canHaveGames(): bool
    {
        if (is_null($this->status) || is_null($this->mode))
            return false;
        return $this->status->canHaveGames() && $this->mode->canHaveGames();
    }
}
