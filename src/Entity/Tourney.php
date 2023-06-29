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

    #[ORM\Column(name: 'sort_order', nullable: true)]
    private ?int $order = null;

    #[ORM\OneToMany(mappedBy: 'tourney', targetEntity: TourneyEntry::class, orphanRemoval: true, cascade: ['persist'])]
    private Collection $entries;

    public const MODE_SINGLE_ELIMINATION = 'se';
    public const MODE_DOUBLE_ELIMINATION = 'de';

    #[ORM\Column(type: 'string', length: 2)]
    #[Assert\Choice(choices: [self::MODE_SINGLE_ELIMINATION, self::MODE_DOUBLE_ELIMINATION], message: 'Invalid tourney mode: {{ value }}')]
    private ?string $mode = null;

    public const RESULT_TYPE_POINTS = 'pt';
    public const RESULT_TYPE_WON_LOST = 'wl';

    #[ORM\Column(type: 'string', length: 2)]
    #[Assert\Choice(choices: [self::RESULT_TYPE_POINTS, self::RESULT_TYPE_WON_LOST], message: 'Invalid result type: {{ value }}')]
    private ?string $result_type = null;

    #[ORM\OneToMany(mappedBy: 'tourney', targetEntity: TourneyGame::class, orphanRemoval: true, cascade: ['persist'])]
    private Collection $games;

    use EntityHistoryTrait;

    public function __construct()
    {
        $this->entries = new ArrayCollection();
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
     * @return Collection<int, TourneyEntry>
     */
    public function getEntries(): Collection
    {
        return $this->entries;
    }

    public function addEntry(TourneyEntry $team): self
    {
        if (!$this->entries->contains($team)) {
            $this->entries->add($team);
            $team->setTourney($this);
        }

        return $this;
    }

    public function removeEntry(TourneyEntry $team): self
    {
        if ($this->entries->removeElement($team)) {
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

    public function getResultType(): ?string
    {
        return $this->result_type;
    }

    public function setResultType(string $result_type): self
    {
        $this->result_type = $result_type;

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

    public function getOrder(): ?int
    {
        return $this->order;
    }

    public function setOrder(?int $order): Tourney
    {
        $this->order = $order;

        return $this;
    }

    public function showPoints(): bool
    {
        return $this->getResultType() == self::RESULT_TYPE_POINTS;
    }
}
