<?php

namespace App\Entity\Admin\EMail;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\Admin\EMail\EMailSendingRepository")
 * @ORM\HasLifecycleCallbacks
 */
class EmailSending
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="integer")
     */

    /**
     * @ORM\Column(type="datetime")
     */
    private $created;

    /**
     * @ORM\Column(type="datetime")
     */
    private $last_modified;

    /**
     * @ORM\Column(type="boolean")
     */
    private $ready_to_send;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\Admin\EMail\EMailTemplate", inversedBy="emailSendings")
     * @ORM\JoinColumn(nullable=false)
     */
    private $template;

    /**
     * @ORM\OneToMany(targetEntity="App\Entity\Admin\EMail\EmailSendingTask", mappedBy="emailSending")
     */
    private $EMailSendingTask;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $sent;

    public function __construct()
    {
        $this->Recipient = new ArrayCollection();
        $this->emailSendingTasks = new ArrayCollection();
        $this->EMailSendingTask = new ArrayCollection();
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

    public function getCreated(): ?\DateTimeInterface
    {
        return $this->created;
    }

    public function setCreated(\DateTimeInterface $created): self
    {
        $this->created = $created;

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

    public function getReadyToSend(): ?bool
    {
        return $this->ready_to_send;
    }

    public function setReadyToSend(bool $ready_to_send): self
    {
        $this->ready_to_send = $ready_to_send;

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

    public function getTemplate(): ?EMailTemplate
    {
        return $this->template;
    }

    public function setTemplate(?EMailTemplate $template): self
    {
        $this->template = $template;

        return $this;
    }

    /**
     * @return Collection|EmailSendingTask[]
     */
    public function getEMailSendingTask(): Collection
    {
        return $this->EMailSendingTask;
    }

    public function addEMailSendingTask(EmailSendingTask $eMailSendingTask): self
    {
        if (!$this->EMailSendingTask->contains($eMailSendingTask)) {
            $this->EMailSendingTask[] = $eMailSendingTask;
            $eMailSendingTask->setEmailSending($this);
        }

        return $this;
    }

    public function removeEMailSendingTask(EmailSendingTask $eMailSendingTask): self
    {
        if ($this->EMailSendingTask->contains($eMailSendingTask)) {
            $this->EMailSendingTask->removeElement($eMailSendingTask);
            // set the owning side to null (unless already changed)
            if ($eMailSendingTask->getEmailSending() === $this) {
                $eMailSendingTask->setEmailSending(null);
            }
        }

        return $this;
    }

    public function getSent(): ?\DateTimeInterface
    {
        return $this->sent;
    }

    public function setSent(): self
    {
        $this->sent = new \DateTime();
        $this->setReadyToSend(false);

        return $this;
    }
}
