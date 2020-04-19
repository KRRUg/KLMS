<?php

namespace App\Entity\EMail;

use DateTime;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity(repositoryClass="App\Repository\EMail\EmailSendingTaskRepository")
 * @ORM\HasLifecycleCallbacks
 * @ORM\Table(
 *      name="email_sending_task",
 *      uniqueConstraints={@ORM\UniqueConstraint(columns={"user_id", "email_template_id"})}
 * )
 * @UniqueEntity(
 *      fields={"user_id", "email_template_id"},
 *      message="League for given country already exists in database."
 * )
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
	private $userId;

	/**
	 * @ORM\Column(type="boolean")
	 */
	private $isSent = false;

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
	private $isSendable = false;

	/**
	 * @ORM\Column(type="datetime", nullable=true)
	 */
	private $sent;


	/**
	 * @ORM\ManyToOne(targetEntity="App\Entity\EMail\EMailTemplate", inversedBy="emailSendingTasks")
	 * @ORM\JoinColumn(nullable=false)
	 */
	private $EMailTemplate;


	public function getId(): ?int
	{
		return $this->id;
	}


	public function getRecipientId(): ?string
	{
		return $this->userId;
	}

	public function setRecipientId(string $userId): self
	{
		$this->userId = $userId;
		return $this;
	}

	public function getIsSent(): ?bool
	{
		return $this->isSent;
	}

	public function setIsSent(): self
	{
		$this->isSent = true;
		$this->setIsSendable(false);
		$this->setSent(new DateTime());
		return $this;
	}

	private function setSent(DateTimeInterface $sent): self
	{
		$this->sent = $sent;

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

	public function getIsSendable(): ?bool
	{
		return $this->isSendable;
	}

	public function setIsSendable(bool $isSendable): self
	{
		$this->isSendable = $isSendable;

		return $this;
	}

	public function getSent(): ?DateTimeInterface
	{
		return $this->sent;
	}

	public function getEMailTemplate(): ?EMailTemplate
	{
		return $this->EMailTemplate;
	}

	public function setEMailTemplate(?EMailTemplate $EMailTemplate): self
	{
		$this->EMailTemplate = $EMailTemplate;

		return $this;
	}


}
