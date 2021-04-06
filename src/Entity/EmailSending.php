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
     * @ORM\OneToOne(targetEntity="App\Entity\EMailTemplate", inversedBy="emailSending", cascade={"persist"})
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
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $finished;

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

    public function getTemplate(): ?EMailTemplate
    {
        return $this->template;
    }

    public function setTemplate(EMailTemplate $template): self
    {
        $this->template = $template;

        return $this;
    }

    public function getCreated(): ?DateTimeInterface
    {
        return $this->created;
    }

    public function setCreated(): self
    {
        if ($this->created == null)
            $this->created = new DateTime();

        return $this;
    }

    public function getStarted(): ?DateTimeInterface
    {
        return $this->started;
    }

    public function setStarted(DateTimeInterface $started = null): self
    {
        $this->started = $started;

        return $this;
    }

    public function getFinished()
    {
        return $this->finished;
    }

    public function setFinished($finished): self
    {
        $this->finished = $finished;
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
        return is_null($this->started) && is_null($this->finished);
    }

	public function isInSending(): bool
	{
		return !is_null($this->started) && is_null($this->finished);
	}

    public function isFinished(): bool
    {
        return !is_null($this->started) && !is_null($this->finished);
    }
}
