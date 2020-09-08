<?php


namespace App\Entity;

use App\Helper\HistoryAwareEntity;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use Vich\UploaderBundle\Entity\File as EmbeddedFile;
use Symfony\Component\HttpFoundation\File\File;

/**
 * @ORM\Entity(repositoryClass="MediaRepository")
 * @ORM\HasLifecycleCallbacks
 * @Vich\Uploadable
 */
class Media implements HistoryAwareEntity
{
    const MAX_FILE_SIZE = "4096k";
    const MIME_TYPES = ["image/png", "image/jpeg", "image/gif", "application/pdf"];

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
     *     mimeTypesMessage = "Please upload a valid file (Image or PDF)"
     * )
     * @Vich\UploadableField(
     *     mapping="media",
     *     fileNameProperty="media.name",
     *     size="media.size",
     *     mimeType="media.mimeType",
     *     originalName="media.originalName",
     *     dimensions="media.dimensions"
     * )
     * @var File
     */
    private $mediaFile;

    /**
     * @ORM\Embedded(class="Vich\UploaderBundle\Entity\File")
     * @var EmbeddedFile
     */
    private $media;

    use Traits\EntityHistoryTrait;

    /**
     * Image constructor.
     */
    public function __construct()
    {
        $this->media = new EmbeddedFile();
    }

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
            $this->setLastModified(new \DateTime());
        }
    }

    public function setMedia(EmbeddedFile $media): void
    {
        $this->media = $media;
    }

    public function getMedia(): ?EmbeddedFile
    {
        return $this->media;
    }

    private function checkMediaType(string $prefix): bool
    {
        $mimeType = null;
        if (!empty($this->media->getMimeType())) {
            $mimeType = $this->media->getMimeType();
        } else if (!empty($this->getMediaFile())) {
            $mimeType = $this->getMediaFile()->getMimeType();
        }
        return substr($mimeType, 0, strlen($prefix)) === $prefix;
    }

    public function isImage(): bool
    {
        return $this->checkMediaType("image/");
    }

    public function isDocument(): bool
    {
        return $this->checkMediaType("document/");
    }
}