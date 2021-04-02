<?php

namespace App\Entity\EMail;

use DateTime;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\EMail\EMailTemplateRepository")
 * @ORM\HasLifecycleCallbacks
 */
class EMailTemplate
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $subject;

    /**
     * @ORM\Column(type="datetime")
     */
    // TODO use EntityHistoryTrait
    private $last_modified;

    /**
     * @ORM\Column(type="datetime")
     */
    // TODO use EntityHistoryTrait
    private $created;

    /**
     * @ORM\Column(type="boolean")
     */
    private $isPublished = true;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $body = '';

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $DesignFile;

    /**
     * @ORM\Column(type="string", length=255, nullable=true, unique=true)
     */
    private $applicationHook;

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\EMail\EmailSending", mappedBy="EMailTemplate", cascade={"persist", "remove"})
     */
    private $emailSending;


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

    public function getSubject(): ?string
    {
        return $this->subject;
    }

    public function setSubject(string $subject): self
    {
        $this->subject = $subject;

        return $this;
    }

    public function getLastModified(): ?DateTimeInterface
    {
        return $this->last_modified;
    }

    public function setLastModified(DateTimeInterface $last_modified): self
    {
        $this->last_modified = $last_modified;

        return $this;
    }

    /**
     * @ORM\PrePersist()
     * @ORM\PreUpdate()
     */
    // TODO use EntityHistoryTrait
    public function updateModifiedDatetime()
    {
        // update the modified time and creation time
        $this->setLastModified(new DateTime());
        if ($this->getCreated() === null) {
            $this->setCreated(new DateTime());
        }
    }

    public function getCreated(): ?DateTimeInterface
    {
        return $this->created;
    }

    public function setCreated(DateTimeInterface $created): self
    {
        $this->created = $created;

        return $this;
    }

    /**
     * @return bool
     */
    public function isManualSendable(): bool
    {
        return $this->isPublished && !$this->isApplicationHooked();
    }

    /**
     * @return bool
     */
    public function isApplicationHooked()
    {
        return $this->getApplicationHook() != null && !empty($this->getApplicationHook());
    }

    /**
     * @return string|null
     */
    public function getApplicationHook(): ?string
    {
        return $this->applicationHook;
    }

    public function setApplicationHook(?string $ApplicationHookName): self
    {
        $this->applicationHook = $ApplicationHookName;
        return $this;
    }

    public function getBody(): ?string
    {
        return $this->body;
    }

    public function setBody(string $body = null): self
    {
        $this->body = $body ?? '';
        return $this;
    }

    public function isPublished(): ?bool
    {
        return $this->isPublished;
    }

    public function setPublished(bool $published): self
    {
        $this->isPublished = $published;
        return $this;
    }

    public function isEditable(): ?bool
    {
        return true;
    }

    public function isDeletable(): ?bool
    {
        return !$this->isApplicationHooked();
    }

    public function getDesignFile(): ?string
    {
        return $this->DesignFile;
    }

    public function setDesignFile(string $DesignFile): self
    {
        $this->DesignFile = $DesignFile;

        return $this;
    }

    public function getEmailSending(): ?EmailSending
    {
        return $this->emailSending;
    }

    public function setEmailSending(EmailSending $emailSending): self
    {
        $this->emailSending = $emailSending;

        // set the owning side of the relation if necessary
        if ($emailSending->getEMailTemplate() !== $this) {
            $emailSending->setEMailTemplate($this);
        }

        return $this;
    }
}
