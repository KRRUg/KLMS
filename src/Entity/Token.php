<?php

namespace App\Entity;

use App\Repository\TokenRepository;
use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\UuidInterface;

#[ORM\Entity(repositoryClass: TokenRepository::class)]
class Token
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 20)]
    private ?string $selector = null;

    #[ORM\Column(type: 'uuid')]
    private ?UuidInterface $userUuid = null;

    #[ORM\Column(type: 'string', length: 100)]
    private ?string $hash = null;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $type = null;

    #[ORM\Column(type: 'datetime')]
    private ?DateTimeInterface $requestedAt = null;

    #[ORM\Column(type: 'datetime')]
    private ?DateTimeInterface $expiresAt = null;

    public function __construct()
    {
        $this->requestedAt = new DateTimeImmutable('now');
    }

    public function getSelector(): ?string
    {
        return $this->selector;
    }

    public function setSelector(string $selector): self
    {
        $this->selector = $selector;

        return $this;
    }

    public function getHash(): ?string
    {
        return $this->hash;
    }

    public function setHash(string $hash): self
    {
        $this->hash = $hash;

        return $this;
    }

    public function getRequestedAt(): ?DateTimeInterface
    {
        return $this->requestedAt;
    }

    public function setRequestedAt(DateTimeInterface $requestedAt): self
    {
        $this->requestedAt = $requestedAt;

        return $this;
    }

    public function getExpiresAt(): ?DateTimeInterface
    {
        return $this->expiresAt;
    }

    public function setExpiresAt(DateTimeInterface $expiresAt): self
    {
        $this->expiresAt = $expiresAt;

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

    public function getUserUuid(): ?UuidInterface
    {
        return $this->userUuid;
    }

    public function setUserUuid(UuidInterface $userUuid): self
    {
        $this->userUuid = $userUuid;

        return $this;
    }

    public function isExpired(): bool
    {
        return $this->expiresAt->getTimestamp() <= \time();
    }
}
