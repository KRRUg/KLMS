<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\UuidInterface;

#[ORM\Entity]
class PushSubscription
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 500, unique: true)]
    private ?string $endpoint = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $publicKey = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $authToken = null;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $contentEncoding = null;

    #[ORM\Column(type: 'uuid', nullable: true)]
    private ?UuidInterface $gamer = null;

    public function getId(): ?int
    {
        return $this->id;
    }
    public function getEndpoint(): ?string
    {
        return $this->endpoint;
    }
    public function setEndpoint(string $endpoint): self
    {
        $this->endpoint = $endpoint;
        return $this;
    }
    public function getPublicKey(): ?string
    {
        return $this->publicKey;
    }
    public function setPublicKey(?string $publicKey): self
    {
        $this->publicKey = $publicKey;
        return $this;
    }
    public function getAuthToken(): ?string
    {
        return $this->authToken;
    }
    public function setAuthToken(?string $authToken): self
    {
        $this->authToken = $authToken;
        return $this;
    }
    public function getContentEncoding(): ?string
    {
        return $this->contentEncoding;
    }
    public function setContentEncoding(?string $contentEncoding): self
    {
        $this->contentEncoding = $contentEncoding;
        return $this;
    }

    public function getGamer(): ?UuidInterface
    {
        return $this->gamer;
    }

    public function setGamer(?UuidInterface $gamer): self
    {
        $this->gamer = $gamer;
        return $this;
    }
}
