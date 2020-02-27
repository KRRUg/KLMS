<?php

namespace App\Entity\Admin\EMail;

use App\Entity\Admin\Email\EMailSending;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="EmailSendingTaskRepository")
 * @ORM\HasLifecycleCallbacks
 */
class EmailSendingTask
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
    //TODO: Nur temporär, da muss dann ein echter User rein, sobald er verfügbar ist
    private $Recipient;

    /**
     * @ORM\Column(type="boolean")
     */
    private $isSent;

    /**
     * @ORM\Column(type="datetime")
     */
    private $created;


    /**
     * @ORM\Column(type="datetime")
     */
    private $last_modified;

    /**
     * @ORM\ManyToMany(targetEntity="App\Entity\Admin\EMail\EMailSending", inversedBy="emailSendingTasks")
     */
    private $EMailSending;

    public function __construct()
    {
        $this->EMailSending = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }


    public function getRecipient(): ?string
    {
        return $this->Recipient;
    }

    public function setRecipient(string $Recipient): self
    {
        $this->Recipient = $Recipient;

        return $this;
    }

    public function getIsSent(): ?bool
    {
        return $this->isSent;
    }

    public function setIsSent(bool $isSent): self
    {
        $this->isSent = $isSent;

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

    /**
     * @return Collection|EMailSending[]
     */
    public function getEMailSending(): Collection
    {
        return $this->EMailSending;
    }

    public function addEMailSending(EMailSending $eMailSending): self
    {
        if (!$this->EMailSending->contains($eMailSending)) {
            $this->EMailSending[] = $eMailSending;
        }

        return $this;
    }

    public function removeEMailSending(EMailSending $eMailSending): self
    {
        if ($this->EMailSending->contains($eMailSending)) {
            $this->EMailSending->removeElement($eMailSending);
        }

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


}
