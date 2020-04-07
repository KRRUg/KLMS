<?php

namespace App\Entity\Admin\EMail;

use DateTime;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\Admin\EMail\EMailTemplateRepository")
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
    private $last_modified;

    /**
     * @ORM\Column(type="datetime")
     */
    private $created;

    /**
     * @ORM\Column(type="boolean")
     */
    private $isPublished;

    /**
     * @ORM\Column(type="text")
     */
    private $body = '';

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\Admin\EMail\EmailSending", mappedBy="template", cascade={"persist", "remove"})
     */
    private $emailSending;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $DesignFile;


    public function __construct()
    {
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
     * @ORM\PrePersist()
     * @ORM\PreUpdate()
     */
    public function updateModifiedDatetime()
    {
        // update the modified time and creation time
        $this->setLastModified(new DateTime());
        if ($this->getCreated() === null) {
            $this->setCreated(new DateTime());
        }
    }

    public function getIsPublished(): ?bool
    {
        return $this->isPublished;
    }

    public function setIsPublished(bool $published): self
    {
        $this->isPublished = $published;
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

    public function getIsEditable(): ?bool
    {
        return $this->getEmailSending() ? $this->getEmailSending()->getIsEditable() : true;
    }

    public function getIsDeleteable(): ?bool
    {
        return $this->getEmailSending() == null || $this->getEmailSending()->getIsDeleteable();
    }

    /**
     * @return Collection|EmailSendingTask[]
     */
    public function getEmailSendingTasks(): Collection
    {
        return $this->getEmailSending() ? $this->getEmailSending()->getEMailSendingTask() : new ArrayCollection();
    }

    /**
     * @return Collection|EmailSendingTask[]
     */
    public function getCompletedEmailSendingTasks(): Collection
    {
        $sendingTasks = $this->getEmailSendingTasks()->filter(function (EmailSendingTask $t) {
            return $t->getIsSent();
        });

        return $sendingTasks;
    }

    public function getEmailSending(): ?EmailSending
    {
        return $this->emailSending;
    }

    public function setEmailSending(?EmailSending $emailSending): self
    {
        $this->emailSending = $emailSending;

        // set (or unset) the owning side of the relation if necessary
        $newTemplate = null === $emailSending ? null : $this;
        if ($emailSending->getTemplate() !== $newTemplate) {
            $emailSending->setTemplate($newTemplate);
        }

        return $this;
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


}
