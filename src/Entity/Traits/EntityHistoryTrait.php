<?php

namespace App\Entity\Traits;

use DateTime;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * This trait adds all history related fields to an entity.
 * An entity using this trait fulfils the HistoryAwareEntity interface. The class is
 * required to have the HasLifecycleCallbacks ORM annotation.
 *
 * @see App\Entity\Traits\HistoryAwareEntity
 *
 * @ORM\HasLifecycleCallbacks
 */
trait EntityHistoryTrait
{
    /**
     * @ORM\Column(type="uuid", nullable=false)
     * @Assert\Uuid(strict=false)
     */
    private ?UuidInterface $authorId = null;

    /**
     * @ORM\Column(type="uuid", nullable=false)
     * @Assert\Uuid(strict=false)
     */
    private ?UuidInterface $modifierId = null;

    /**
     * @ORM\Column(type="datetime", nullable=false)
     * @Assert\GreaterThanOrEqual(propertyPath="created")
     * @Assert\Type(type="DateTime")
     */
    private ?DateTimeInterface $last_modified = null;

    /**
     * @ORM\Column(type="datetime", nullable=false)
     * @Assert\Type(type="DateTime")
     */
    private ?DateTimeInterface $created = null;

    public function getAuthorId(): ?UuidInterface
    {
        return $this->authorId;
    }

    public function setAuthorId(?UuidInterface $authorId): self
    {
        $this->authorId = $authorId;

        return $this;
    }

    public function getModifierId(): ?UuidInterface
    {
        return $this->modifierId;
    }

    public function setModifierId(?UuidInterface $modifierId): self
    {
        $this->modifierId = $modifierId;

        return $this;
    }

    public function getLastModified(): ?DateTimeInterface
    {
        return $this->last_modified;
    }

    public function setLastModified(?DateTimeInterface $last_modified): self
    {
        $this->last_modified = $last_modified;

        return $this;
    }

    public function getCreated(): ?DateTimeInterface
    {
        return $this->created;
    }

    public function setCreated(?DateTimeInterface $created): self
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
}
