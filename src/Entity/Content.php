<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass="ContentRepository")
 * @ORM\HasLifecycleCallbacks
 */
class Content
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
     * @ORM\Column(type="datetime")
     */
    private $last_modified;

    /**
     * @ORM\Column(type="datetime")
     */
    private $created;

    /**
     * @ORM\Column(type="boolean")
     */
    private $published;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private $description;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $publishedFrom;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     */
    private $publishedTo;

    /**
     * @ORM\ManyToOne(targetEntity="App\Entity\ContentCategory", inversedBy="contents")
     */
    private $category;

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

    public function getPublished(): ?bool
    {
        return $this->published;
    }

    public function setPublished(bool $published): self
    {
        $this->published = $published;

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

    public function getPublishedFrom(): ?\DateTimeInterface
    {
        return $this->publishedFrom;
    }

    public function setPublishedFrom(?\DateTimeInterface $publishedFrom): self
    {
        $this->publishedFrom = $publishedFrom;

        return $this;
    }

    public function getPublishedTo(): ?\DateTimeInterface
    {
        return $this->publishedTo;
    }

    public function setPublishedTo(?\DateTimeInterface $publishedTo): self
    {
        $this->publishedTo = $publishedTo;

        return $this;
    }

    public function getCategory(): ?ContentCategory
    {
        return $this->category;
    }

    public function setCategory(?ContentCategory $category): self
    {
        $this->category = $category;

        return $this;
    }
}
