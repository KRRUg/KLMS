<?php

namespace App\Entity\Traits;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * This trait adds all history related fields to an entity.
 * An entity using this trait fulfils the HistoryAwareEntity interface. The class is
 * required to have the HasLifecycleCallbacks ORM annotation.
 *
 * @see App\Helper\HistoryAwareEntity
 *
 * @ORM\HasLifecycleCallbacks
 */
trait EntityHistoryTrait
{
    /**
     * @ORM\Column(type="guid", nullable=false)
     * @Assert\Uuid(strict=false)
     */
    private $authorId;

    /**
     * @ORM\Column(type="guid", nullable=false)
     * @Assert\Uuid(strict=false)
     */
    private $modifierId;

    /**
     * @ORM\Column(type="datetime", nullable=false)
     * @Assert\DateTime
     * @Assert\GreaterThanOrEqual(propertyPath="created")
     */
    private $last_modified;

    /**
     * @ORM\Column(type="datetime", nullable=false)
     * @Assert\DateTime
     */
    private $created;


    public function getAuthorId()
    {
        return $this->authorId;
    }

    public function setAuthorId($authorId): self
    {
        $this->authorId = $authorId;
        return $this;
    }

    public function getModifierId()
    {
        return $this->modifierId;
    }

    public function setModifierId($modifierId): self
    {
        $this->modifierId = $modifierId;
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
    public function updateModifiedDatetime() {
        // update the modified time and creation time
        $this->setLastModified(new \DateTime());
        if ($this->getCreated() === null) {
            $this->setCreated(new \DateTime());
        }
    }
}