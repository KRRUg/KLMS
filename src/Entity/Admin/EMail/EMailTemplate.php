<?php

namespace App\Entity\Admin\EMail;

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
     * @ORM\Column(type="text")
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
    private $published;

    /**
     * @ORM\Column(type="text")
     */
    private $body;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Admin\EMail\EmailSending", mappedBy="template")
     */
    private $emailSendings;

    public function __construct()
    {
        $this->emailSendings = new ArrayCollection();
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

    public function getLastModified(): ?\DateTimeInterface
    {
        return $this->last_modified;
    }

    public function setLastModified(\DateTimeInterface $last_modified): self
    {
        $this->last_modified = $last_modified;

        return $this;
    }


    public function getCreated(): ?\DateTimeInterface
    {
        return $this->created;
    }

    public function setCreated(\DateTimeInterface $created): self
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
        $this->setLastModified(new \DateTime());
        if ($this->getCreated() === null) {
            $this->setCreated(new \DateTime());
        }
    }

    public function getPublished(): ?bool
    {
        return $this->published;
    }

    public function setPublished(bool $published): self
    {
        $this->published = $published;

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

    /**
     * @return Collection|EmailSending[]
     */
    public function getEmailSendings(): Collection
    {
        return $this->emailSendings;
    }

    public function addEmailSending(EmailSending $emailSending): self
    {
        if (!$this->emailSendings->contains($emailSending)) {
            $this->emailSendings[] = $emailSending;
            $emailSending->setTemplate($this);
        }

        return $this;
    }

    public function removeEmailSending(EmailSending $emailSending): self
    {
        if ($this->emailSendings->contains($emailSending)) {
            $this->emailSendings->removeElement($emailSending);
            // set the owning side to null (unless already changed)
            if ($emailSending->getTemplate() === $this) {
                $emailSending->setTemplate(null);
            }
        }

        return $this;
    }
}
