<?php

namespace App\Entity;

use App\Helper\HistoryAwareEntity;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\EMailTemplateRepository")
 * @ORM\HasLifecycleCallbacks
 */
class EMailTemplate implements HistoryAwareEntity
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
     * @ORM\Column(type="boolean")
     */
    private $isPublished = true;

    /**
     * @ORM\Column(type="text")
     */
    private $body = '';

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $designFile;

    /**
     * @ORM\OneToOne(targetEntity="App\Entity\EmailSending", mappedBy="EMailTemplate", cascade={"persist", "remove"})
     */
    private $emailSending;

    use Traits\EntityHistoryTrait;

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

    public function getDesignFile(): ?string
    {
        return $this->designFile;
    }

    public function setDesignFile(string $designFile): self
    {
        $this->designFile = $designFile;

        return $this;
    }
}
