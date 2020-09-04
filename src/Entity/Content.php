<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Helper\AuthorStoringEntity;

/**
 * @ORM\Entity(repositoryClass="App\Repository\ContentRepository")
 * @ORM\HasLifecycleCallbacks
 */
class Content implements AuthorStoringEntity
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
    private $title;

    /**
     * @ORM\Column(type="text")
     */
    private $content;

    /**
     * @ORM\Column(type="guid", nullable=false)
     */
    private $authorId;

    /**
     * @ORM\Column(type="guid", nullable=false)
     */
    private $modifierId;

    /**
     * @ORM\Column(type="datetime", nullable=false)
     */
    private $last_modified;

    /**
     * @ORM\Column(type="datetime", nullable=false)
     */
    private $created;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $description;

    /**
     * @ORM\Column(type="string", length=255, nullable=true, unique=true)
     */
    private $alias;

    public function __construct()
    {
        $this->title = '';
        $this->content = '';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;

        return $this;
    }

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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getAlias(): ?string
    {
        return $this->alias;
    }

    public function setAlias(?string $alias): self
    {
        $this->alias = $alias;

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
