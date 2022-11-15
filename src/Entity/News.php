<?php

namespace App\Entity;

use App\Entity\Traits\HistoryAwareEntity;
use DateTime;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Entity\File as EmbeddedFile;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

/**
 * @ORM\Entity(repositoryClass="App\Repository\NewsRepository")
 * @ORM\HasLifecycleCallbacks
 * @Vich\Uploadable
 */
class News implements HistoryAwareEntity
{
    use Traits\EntityHistoryTrait;
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
     * @ORM\Column(type="datetime", nullable=true)
     * @Assert\DateTime()
     */
    private $publishedFrom;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @Assert\DateTime()
     * @Assert\GreaterThan(propertyPath="publishedFrom")
     */
    private $publishedTo;

    /**
     * @Vich\UploadableField(mapping="news", fileNameProperty="image.name", size="image.size", mimeType="image.mimeType", originalName="image.originalName", dimensions="image.dimensions")
     *
     * @var File|null
     */
    private $imageFile;

    /**
     * @ORM\Embedded(class="Vich\UploaderBundle\Entity\File")
     *
     * @var EmbeddedFile
     */
    private $image;

    public function __construct()
    {
        $this->image = new EmbeddedFile();
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

    public function setImageFile(?File $imageFile = null): void
    {
        $this->imageFile = $imageFile;

        if (null !== $imageFile) {
            // It is required that at least one field changes if you are using doctrine
            // otherwise the event listeners won't be called and the file is lost
            $this->setLastModified(new DateTime());
        }
    }

    public function getImageFile(): ?File
    {
        return $this->imageFile;
    }

    public function setImage(EmbeddedFile $image): void
    {
        $this->image = $image;
    }

    public function getImage(): ?EmbeddedFile
    {
        return $this->image;
    }

    public function getPublishedFrom(): ?DateTimeInterface
    {
        return $this->publishedFrom;
    }

    public function setPublishedFrom(?DateTimeInterface $publishedFrom): self
    {
        $this->publishedFrom = $publishedFrom;

        return $this;
    }

    public function getPublishedTo(): ?DateTimeInterface
    {
        return $this->publishedTo;
    }

    public function setPublishedTo(?DateTimeInterface $publishedTo): self
    {
        $this->publishedTo = $publishedTo;

        return $this;
    }

    public function isActive(): bool
    {
        $now = new DateTime();

        return (empty($this->getPublishedFrom()) || $this->getPublishedFrom() <= $now)
            && (empty($this->getPublishedTo()) || $this->getPublishedTo() >= $now);
    }

    public function activeSince(): DateTimeInterface
    {
        if (empty($this->publishedFrom)) {
            return $this->getCreated();
        } else {
            return $this->getPublishedFrom();
        }
    }
}
