<?php

namespace App\Entity\EMail;

use DateTime;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="App\Repository\EMail\EmailSendingRepository")
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
	 * @ORM\OneToOne(targetEntity="App\Entity\EMail\EMailTemplate", inversedBy="emailSending", cascade={"persist", "remove"})
	 * @ORM\JoinColumn(nullable=false)
	 */
	private $EMailTemplate;

	/**
	 * @ORM\Column(type="string", length=255)
	 */
	private $recipientGroup;

	/**
	 * @ORM\Column(type="string", length=255)
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
	private $recipientCountSent = 0;

	/**
	 * @ORM\Column(type="integer")
	 */
	private $recipientCountGenerated = 0;

	/**
	 * @ORM\Column(type="boolean")
	 */
	private $isPublished = false;

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

	public function setStatus(string $status): self
	{
		$this->status = $status;

		return $this;
	}


	public function getCreated(): ?DateTimeInterface
	{
		return $this->created;
	}

	/**
	 * @param DateTimeInterface $created
	 *
	 * @return EmailSending
	 * @throws \Exception
	 *
	 * @ORM\PrePersist()
	 */


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

	public function setStartTime(DateTimeInterface $startTime): self
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

	public function getRecipientCountSent(): ?int
	{
		return $this->recipientCountSent;
	}

	public function setRecipientCountSent(int $recipientCountSent): self
	{
		$this->recipientCountSent = $recipientCountSent;

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

	public function getIsPublished(): ?bool
	{
		return $this->isPublished;
	}

	public function setIsPublished(bool $isPublished): self
	{
		$this->isPublished = $isPublished;

		return $this;
	}

//Calculated Properties
	public function getIsEditable()
	{
		return true;//TODO implementieren!
	}

	public function getIsDeletable()
	{
		return true;//TODO implementieren!
	}


}
