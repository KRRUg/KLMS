<?php

namespace App\Entity;

use App\Repository\PollVoteRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PollVoteRepository::class)]
#[ORM\Table(name: 'poll_vote')]
#[ORM\UniqueConstraint(name: 'uniq_poll_vote_poll_user', columns: ['poll_id', 'user_uuid'])]
#[ORM\UniqueConstraint(name: 'uniq_poll_vote_poll_fingerprint', columns: ['poll_id', 'fingerprint_hash'])]
class PollVote
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Poll::class, inversedBy: 'votes')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Poll $poll;

    #[ORM\ManyToOne(targetEntity: PollOption::class, inversedBy: 'votes')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private PollOption $option;

    #[ORM\Column(type: 'guid', nullable: true)]
    private ?string $userUuid = null;

    #[ORM\Column(type: 'string', length: 64)]
    private string $fingerprintHash;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new DateTimeImmutable();
        $this->fingerprintHash = '';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getPoll(): Poll
    {
        return $this->poll;
    }

    public function setPoll(Poll $poll): self
    {
        $this->poll = $poll;

        return $this;
    }

    public function getOption(): PollOption
    {
        return $this->option;
    }

    public function setOption(PollOption $option): self
    {
        $this->option = $option;

        return $this;
    }

    public function getUserUuid(): ?string
    {
        return $this->userUuid;
    }

    public function setUserUuid(?string $userUuid): self
    {
        $this->userUuid = $userUuid;

        return $this;
    }

    public function getFingerprintHash(): string
    {
        return $this->fingerprintHash;
    }

    public function setFingerprintHash(string $fingerprintHash): self
    {
        $this->fingerprintHash = $fingerprintHash;

        return $this;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }
}
