<?php

namespace App\Entity;

use App\Entity\Traits\HistoryAwareEntity;
use App\Repository\SponsorRepository;
use DateTime;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Entity\File as EmbeddedFile;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[ORM\Entity(repositoryClass: SponsorRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[Vich\Uploadable]
class Sponsor implements HistoryAwareEntity
{
    use Traits\EntityHistoryTrait;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\NotBlank]
    private ?string $name = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    #[Assert\Url]
    private ?string $url = null;

    #[Vich\UploadableField(mapping: 'sponsor', fileNameProperty: 'logo.name', size: 'logo.size', mimeType: 'logo.mimeType', originalName: 'logo.originalName', dimensions: 'logo.dimensions')]
    private ?File $logoFile = null;

    #[ORM\Embedded(class: 'Vich\UploaderBundle\Entity\File')]
    private EmbeddedFile $logo;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $text = null;

    #[ORM\ManyToOne(inversedBy: 'sponsors')]
    #[ORM\JoinColumn(nullable: false)]
    private ?SponsorCategory $category = null;

    public function __construct()
    {
        $this->logo = new EmbeddedFile();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $url): self
    {
        $this->url = $url;

        return $this;
    }

    public function setLogoFile(?File $logoFile = null): self
    {
        $this->logoFile = $logoFile;

        if (null !== $logoFile) {
            // It is required that at least one field changes if you are using doctrine
            // otherwise the event listeners won't be called and the file is lost
            $this->setLastModified(new DateTime());
        }

        return $this;
    }

    public function getLogoFile(): ?File
    {
        return $this->logoFile;
    }

    public function setLogo(EmbeddedFile $logo): void
    {
        $this->logo = $logo;
    }

    public function getLogo(): ?EmbeddedFile
    {
        return $this->logo;
    }

    public function getText(): ?string
    {
        return $this->text;
    }

    public function setText(?string $text): self
    {
        $this->text = $text;

        return $this;
    }

    public function getCategory(): ?SponsorCategory
    {
        return $this->category;
    }

    public function setCategory(?SponsorCategory $category): self
    {
        $this->category = $category;

        return $this;
    }
}
