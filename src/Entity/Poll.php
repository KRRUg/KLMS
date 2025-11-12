<?php

namespace App\Entity;

use App\Repository\PollRepository;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PollRepository::class)]
#[ORM\Table(name: 'poll')]
class Poll
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $startAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $endAt = null;

    #[ORM\Column(type: 'string', length: 255)]
    private string $question;

    #[ORM\Column(type: 'boolean')]
    private bool $onlyRegistered = false;

    /**
     * @var Collection<int, PollOption>
     */
    #[ORM\OneToMany(mappedBy: 'poll', targetEntity: PollOption::class, cascade: ['persist'], orphanRemoval: true)]
    #[ORM\OrderBy(['position' => 'ASC', 'id' => 'ASC'])]
    private Collection $options;

    /**
     * @var Collection<int, PollVote>
     */
    #[ORM\OneToMany(mappedBy: 'poll', targetEntity: PollVote::class)]
    private Collection $votes;

    public function __construct()
    {
        $this->startAt = new DateTimeImmutable();
        $this->question = '';
        $this->options = new ArrayCollection();
        $this->votes = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getStartAt(): DateTimeImmutable
    {
        return $this->startAt;
    }

    public function setStartAt(DateTimeImmutable $startAt): self
    {
        $this->startAt = $startAt;

        return $this;
    }

    public function getEndAt(): ?DateTimeImmutable
    {
        return $this->endAt;
    }

    public function setEndAt(?DateTimeImmutable $endAt): self
    {
        $this->endAt = $endAt;

        return $this;
    }

    public function getQuestion(): string
    {
        return $this->question;
    }

    public function setQuestion(string $question): self
    {
        $this->question = $question;

        return $this;
    }

    public function isOnlyRegistered(): bool
    {
        return $this->onlyRegistered;
    }

    public function setOnlyRegistered(bool $onlyRegistered): self
    {
        $this->onlyRegistered = $onlyRegistered;

        return $this;
    }

    /**
     * @return Collection<int, PollOption>
     */
    public function getOptions(): Collection
    {
        return $this->options;
    }

    public function addOption(PollOption $option): self
    {
        if (!$this->options->contains($option)) {
            $this->options->add($option);
            $option->setPoll($this);
        }

        return $this;
    }

    public function removeOption(PollOption $option): self
    {
        if ($this->options->removeElement($option)) {
            if ($option->getPoll() === $this) {
                $option->setPoll(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, PollVote>
     */
    public function getVotes(): Collection
    {
        return $this->votes;
    }

    public function isActive(?DateTimeImmutable $reference = null): bool
    {
        $reference ??= new DateTimeImmutable();

        if ($reference < $this->startAt) {
            return false;
        }

        if ($this->endAt !== null && $reference > $this->endAt) {
            return false;
        }

        return true;
    }

    public function hasEnded(?DateTimeImmutable $reference = null): bool
    {
        $reference ??= new DateTimeImmutable();

        return $this->endAt !== null && $reference > $this->endAt;
    }
}
