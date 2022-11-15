<?php

namespace App\Entity;

use App\Repository\UserImageRepository;
use DateTime;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Entity\File as EmbeddedFile;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

/**
 * @ORM\Entity(repositoryClass=UserImageRepository::class)
 * @ORM\HasLifecycleCallbacks
 * @Vich\Uploadable
 */
class UserImage
{
    /**
     * @ORM\Id()
     * @ORM\Column(type="uuid", unique=true)
     */
    private ?UuidInterface $uuid;

    /**
     * @ORM\Embedded(class="Vich\UploaderBundle\Entity\File")
     */
    private ?EmbeddedFile $image;

    /**
     * @Vich\UploadableField(mapping="user", fileNameProperty="image.name", size="image.size", mimeType="image.mimeType", originalName="image.originalName", dimensions="image.dimensions")
     */
    private ?File $imageFile = null;

    /**
     * @ORM\Column(type="datetime", nullable=false)
     */
    private ?DateTimeInterface $lastModified = null;

    public function __construct(?UuidInterface $uuid)
    {
        $this->uuid = $uuid;
        $this->image = new EmbeddedFile();
    }

    public function getUuid(): ?UuidInterface
    {
        return $this->uuid;
    }

    public function setUuid(?UuidInterface $uuid): self
    {
        $this->uuid = $uuid;

        return $this;
    }

    public function getImage(): ?EmbeddedFile
    {
        return $this->image;
    }

    public function setImage(?EmbeddedFile $image): self
    {
        $this->image = $image;

        return $this;
    }

    public function getLastModified(): ?DateTimeInterface
    {
        return $this->lastModified;
    }

    public function setLastModified(?DateTimeInterface $lastModified): self
    {
        $this->lastModified = $lastModified;

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

    public function isEmpty(): bool
    {
        return empty($this->imageFile) && empty($this->image->getName()) && empty($this->image->getSize());
    }

    /**
     * @ORM\PrePersist()
     * @ORM\PreUpdate()
     */
    public function updateModifiedDatetime()
    {
        // update the modified time and creation time
        $this->setLastModified(new DateTime());
    }
}
