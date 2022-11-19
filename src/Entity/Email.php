<?php

namespace App\Entity;

use App\Entity\Traits\HistoryAwareEntity;
use App\Repository\EmailRepository;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\UuidInterface;

#[ORM\Entity(repositoryClass: EmailRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Email implements HistoryAwareEntity
{
    use Traits\EntityHistoryTrait;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id;

    #[ORM\Column(type: 'string', length: 255, nullable: false)]
    private ?string $name;

    #[ORM\Column(type: 'uuid', nullable: true)]
    private ?UuidInterface $recipientGroup;

    #[ORM\Column(type: 'string', length: 255, nullable: false)]
    private ?string $subject;

    #[ORM\Column(type: 'text', nullable: false)]
    private string $body = '';

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $designFile;

    #[ORM\OneToOne(mappedBy: 'template', cascade: ['persist'], fetch: 'EAGER', orphanRemoval: true)]
    private ?EmailSending $emailSending;

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

    public function getRecipientGroup(): ?UuidInterface
    {
        return $this->recipientGroup;
    }

    public function setRecipientGroup($recipientGroup): self
    {
        $this->recipientGroup = $recipientGroup;

        return $this;
    }

    public function getSubject(): ?string
    {
        return $this->subject;
    }

    public function setSubject(string $subject): self
    {
        $this->subject = $subject;

        return $this;
    }

    public function getBody(): ?string
    {
        return $this->body;
    }

    public function setBody(string $body): self
    {
        $this->body = $body;

        return $this;
    }

    public function getDesignFile(): ?string
    {
        return $this->designFile;
    }

    public function setDesignFile(string $designFile): self
    {
        $this->designFile = $designFile;

        return $this;
    }

    public function getEmailSending(): ?EmailSending
    {
        return $this->emailSending;
    }

    public function setEmailSending(?EmailSending $emailSending): self
    {
        $this->emailSending = $emailSending;

        return $this;
    }

    public function wasSent(): bool
    {
        return !empty($this->emailSending);
    }
}
