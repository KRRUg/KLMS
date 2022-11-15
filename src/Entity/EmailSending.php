<?php

namespace App\Entity;

use DateTime;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity()
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
     * @ORM\OneToOne(targetEntity="Email", inversedBy="emailSending", cascade={"persist"})
     * @ORM\JoinColumn(name="template", nullable=false)
     */
    private $template;

    /**
     * @ORM\Column(type="datetime")
     */
    private $created;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $started;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private $recipientCount;

    public function __construct()
    {
        $this->created = new DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTemplate(): ?Email
    {
        return $this->template;
    }

    public function setTemplate(Email $template): self
    {
        $this->template = $template;

        return $this;
    }

    public function getCreated(): ?DateTimeInterface
    {
        return $this->created;
    }

    public function setCreated(DateTimeInterface $dateTime): self
    {
        $this->setCreated($dateTime);

        return $this;
    }

    public function getStarted(): ?DateTimeInterface
    {
        return $this->started;
    }

    public function setStarted(DateTimeInterface $started): self
    {
        $this->started = $started;

        return $this;
    }

    public function getRecipientCount(): ?int
    {
        return $this->recipientCount;
    }

    public function setRecipientCount(int $recipientCount): self
    {
        $this->recipientCount = $recipientCount;

        return $this;
    }

    // Calculated Properties

    public function isNotStarted(): bool
    {
        return is_null($this->started);
    }

    public function isInSending(): bool
    {
        return !is_null($this->started);
    }
}
