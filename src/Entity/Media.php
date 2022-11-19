<?php

namespace App\Entity;

use App\Entity\Traits\HistoryAwareEntity;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

/**
 * @ORM\Entity(repositoryClass="App\Repository\MediaRepository")
 * @ORM\Table(indexes={ @ORM\Index(name="filename_indes", columns={"file_name"}) })
 * @ORM\HasLifecycleCallbacks
 * @Vich\Uploadable
 */
class Media implements HistoryAwareEntity
{
    use Traits\EntityHistoryTrait;
    final public const MAX_FILE_SIZE = '16384k';
    final public const MIME_TYPES = ['image/png', 'image/jpeg', 'image/gif', 'application/pdf', 'application/zip', 'audio/mpeg', 'audio/ogg'];

    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @Assert\File(
     *     maxSize=Media::MAX_FILE_SIZE,
     *     mimeTypes=Media::MIME_TYPES,
     *     mimeTypesMessage = "Please upload a valid file (Image, PDF or ZIP)"
     * )
     * @Vich\UploadableField(
     *     mapping="media",
     *     fileNameProperty="fileName",
     *     mimeType="mimeType",
     *     originalName="displayName"
     * )
     */
    private ?\Symfony\Component\HttpFoundation\File\File $mediaFile = null;

    /**
     * @ORM\Column(name="file_name", nullable=false)
     */
    private $fileName;

    /**
     * @ORM\Column(name="display_name", nullable=false, unique=true)
     */
    private $displayName;

    /**
     * @ORM\Column(name="mime_type", nullable=false)
     */
    private $mimeType;

    public function getId()
    {
        return $this->id;
    }

    public function getMediaFile(): ?File
    {
        return $this->mediaFile;
    }

    public function setMediaFile(?File $mediaFile = null): void
    {
        $this->mediaFile = $mediaFile;

        if (null !== $mediaFile) {
            // It is required that at least one field changes if you are using doctrine
            // otherwise the event listeners won't be called and the file is lost
            $this->setLastModified(new DateTime());
        }
    }

    /**
     * @return mixed
     */
    public function getFileName()
    {
        return $this->fileName;
    }

    public function setFileName($fileName): self
    {
        $this->fileName = $fileName;

        return $this;
    }

    public function getDisplayName()
    {
        return $this->displayName;
    }

    public function setDisplayName($displayName): self
    {
        $this->displayName = $displayName;

        return $this;
    }

    public function getMimeType()
    {
        return $this->mimeType;
    }

    public function setMimeType($mimeType): self
    {
        $this->mimeType = $mimeType;

        return $this;
    }

    private function checkMediaType(string $prefix): bool
    {
        $mimeType = null;
        if (!empty($this->getMimeType())) {
            $mimeType = $this->getMimeType();
        } elseif (!empty($this->getMediaFile())) {
            $mimeType = $this->getMediaFile()->getMimeType();
        }

        return str_starts_with((string) $mimeType, $prefix);
    }

    public function getMediaType()
    {
        $mime = $this->getMimeType();
        if (!preg_match('/^(\w+)\/(\w*)$/', (string) $mime, $output)) {
            return '';
        }

        return $output[1];
    }

    public function isImage(): bool
    {
        return $this->checkMediaType('image/');
    }

    public function isDocument(): bool
    {
        return $this->checkMediaType('application/');
    }

    public function isAudio(): bool
    {
        return $this->checkMediaType('audio/');
    }
}
