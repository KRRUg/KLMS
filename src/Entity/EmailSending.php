<?php

namespace App\Entity;

use DateTime;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\EmailSendingRepository")
 * @ORM\HasLifecycleCallbacks()
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
	 * @ORM\OneToOne(targetEntity="App\Entity\EMailTemplate", inversedBy="emailSending", cascade={"persist", "remove"})
	 * @ORM\JoinColumn(nullable=false)
	 */
	private $EMailTemplate;

	/**
	 * @ORM\Column(type="string", length=255)
	 */
	private $recipientGroup;

	/**
	 * @ORM\Column(type="string", length=255,nullable=true)
	 */
	private $status;

	/**
	 * @ORM\Column(type="datetime")
	 */
	private $created;

	/**
	 * @ORM\Column(type="datetime", nullable=true)
	 */
	private $startTime;

	/**
	 * @ORM\Column(type="integer")
	 */
	private $recipientCount = 0;

	/**
	 * @ORM\Column(type="integer")
	 */
	private $recipientCountGenerated = 0;

	/**
	 * @ORM\Column(type="boolean")
	 */
	private $isPublished = false;

	/**
	 * @ORM\Column(type="boolean")
	 */
	private $isInSending = false;

	/**
	 * @ORM\Column(type="integer")
	 */
	private $errorCount = 0;

	public function getId(): ?int
	{
		return $this->id;
	}

	public function getEMailTemplate(): ?EMailTemplate
	{
		return $this->EMailTemplate;
	}

	public function setEMailTemplate(EMailTemplate $EMailTemplate): self
	{
		$this->EMailTemplate = $EMailTemplate;

		return $this;
	}

	public function getRecipientGroup(): ?string
	{
		return $this->recipientGroup;
	}

	public function setRecipientGroup(string $recipientGroup): self
	{
		$this->recipientGroup = $recipientGroup;

		return $this;
	}

	public function getStatus(): ?string
	{
		return $this->status;
	}

	public function setStatus(string $status = null): self
	{
		/*if ($this->status != null) {
			$this->status .= " => " . $status . "\n";
		} else {
			$this->status = $status . "\n";
		}
		*/
		$this->status = $status;
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

	public function getStartTime(): ?DateTimeInterface
	{
		return $this->startTime;
	}

	public function setStartTime(DateTimeInterface $startTime = null): self
	{
		$this->startTime = $startTime;

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


	public function getRecipientCountGenerated(): ?int
	{
		return $this->recipientCountGenerated;
	}

	public function setRecipientCountGenerated(int $recipientCountGenerated): self
	{
		$this->recipientCountGenerated = $recipientCountGenerated;

		return $this;
	}

	public function getIsEditable()
	{
		return $this->getIsDeletable();
		return true;//TODO implementieren!
	}

	public function getIsDeletable()
	{
		return !$this->getIsInSending();
	}

//Calculated Properties

	public function getIsInSending(): ?bool
	{
		return $this->isInSending;
	}

	public function setIsInSending(bool $isInSending): self
	{
		$this->isInSending = $isInSending;

		return $this;
	}

	public function getIsPublishable()
	{
		return !$this->isPublished;
	}

	public function getIsUnpublishable()
	{
		return $this->getIsPublished() && !$this->getIsInSending();
	}

	public function getIsPublished(): ?bool
	{
		return $this->isPublished;
	}

	public function setIsPublished(bool $isPublished): self
	{
		$this->isPublished = $isPublished;
		return $this;
	}

	public function getIsActiveSending()
	{
		return $this->startTime != null && $this->startTime <= new DateTime() || $this->getIsInSending();
	}

	public function getErrorCount(): ?int
	{
		return $this->errorCount;
	}

	public function setErrorCount(int $errorCount): self
	{
		$this->errorCount = $errorCount;

		return $this;
	}
}
