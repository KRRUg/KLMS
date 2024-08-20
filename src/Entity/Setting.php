<?php

namespace App\Entity;

use App\Repository\SettingRepository;
use DateTime;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[ORM\Table]
#[ORM\Index(columns: ['`key`'], name: 'key_idx')]
#[ORM\Entity(repositoryClass: SettingRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[Vich\Uploadable]
class Setting
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(name: '`key`', type: 'string', length: 255, unique: true)]
    private ?string $key = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $text = null;

    #[ORM\Column(type: 'datetime')]
    private ?DateTimeInterface $last_modified = null;

    #[Vich\UploadableField(mapping: 'setting', fileNameProperty: 'text')]
    private ?File $file = null;

    public function setFile(File $file = null): void
    {
        $this->file = $file;

        if ($file) {
            $this->last_modified = new DateTime('now');
        }
    }

    public function getFile(): ?UploadedFile
    {
        return $this->file;
    }

    public function __construct(string $key)
    {
        $this->key = $key;
        $this->text = '';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getKey(): ?string
    {
        return $this->key;
    }

    public function setKey(string $key): self
    {
        $this->key = $key;

        return $this;
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

    public function getLastModified(): ?DateTimeInterface
    {
        return $this->last_modified;
    }

    public function setLastModified(?DateTimeInterface $last_modified): self
    {
        $this->last_modified = $last_modified;

        return $this;
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function updateModifiedDatetime(): void
    {
        // update the modified time
        $this->setLastModified(new DateTime());
    }
}
