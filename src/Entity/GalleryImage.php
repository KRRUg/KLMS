<?php

namespace App\Entity;

use App\Repository\GalleryImageRepository;
use Doctrine\ORM\Mapping as ORM;
use Ramsey\Uuid\Doctrine\UuidGenerator;
use Ramsey\Uuid\UuidInterface;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[ORM\Entity(repositoryClass: GalleryImageRepository::class)]
#[Vich\Uploadable]
class GalleryImage
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid', unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(class: UuidGenerator::class)]
    private ?UuidInterface $uuid = null;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $imageName = null;

    #[Vich\UploadableField(mapping: 'gallery', fileNameProperty: 'imageName')]
    private ?File $imageFile = null;

    #[ORM\ManyToOne(targetEntity: GalleryEvent::class, inversedBy: 'galleryImages')]
    #[ORM\JoinColumn(nullable: true)]
    private ?GalleryEvent $galleryEvent = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $title = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?UuidInterface 
    { 
        return $this->uuid; 
    }

    public function getUuid(): ?UuidInterface 
    { 
        return $this->uuid; 
    }
    
    public function setUuid(?UuidInterface $uuid): void 
    { 
        $this->uuid = $uuid; 
    }

    public function setImageFile(?File $file = null): void 
    {
        $this->imageFile = $file;
        if ($file) {
            $this->updatedAt = new \DateTimeImmutable();
        }
    }

    public function getImageFile(): ?File 
    { 
        return $this->imageFile; 
    }
    
    public function getImageName(): ?string 
    { 
        return $this->imageName; 
    }
    
    public function setImageName(?string $name): void 
    { 
        $this->imageName = $name; 
    }

    public function getTitle(): ?string 
    { 
        return $this->title; 
    }
    
    public function setTitle(?string $title): void 
    { 
        $this->title = $title; 
    }

    public function getDescription(): ?string 
    { 
        return $this->description; 
    }
    
    public function setDescription(?string $description): void 
    { 
        $this->description = $description; 
    }

    public function getUpdatedAt(): ?\DateTimeImmutable 
    { 
        return $this->updatedAt; 
    }
    
    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): void 
    { 
        $this->updatedAt = $updatedAt; 
    }

    public function getCreatedAt(): \DateTimeImmutable 
    { 
        return $this->createdAt; 
    }
    
    public function setCreatedAt(\DateTimeImmutable $createdAt): void 
    { 
        $this->createdAt = $createdAt; 
    }

    public function getGalleryEvent(): ?GalleryEvent
    {
        return $this->galleryEvent;
    }

    public function setGalleryEvent(?GalleryEvent $galleryEvent): self
    {
        $this->galleryEvent = $galleryEvent;
        return $this;
    }

    /**
     * Get directory name for VichUploader based on event ID
     * This is used by VichUploader's PropertyDirectoryNamer
     */
    public function getEventDirectoryName(): string
    {
        if ($this->galleryEvent) {
            return 'event-' . $this->galleryEvent->getId();
        }
        
        return 'ohne-event';
    }

    public function getImagePath(): string
    {
        $eventPath = $this->getEventDirectoryName();
        return '/images/gallery/' . $eventPath . '/' . $this->imageName;
    }
}
