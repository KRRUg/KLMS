<?php

namespace App\Entity;

use App\Entity\NewsComment;
use App\Entity\Traits\HistoryAwareEntity;
use App\Repository\NewsRepository;
use App\Validator\SafeImageFile;
use DateTime;
use DateTimeInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Validator\Constraints as Assert;
use Vich\UploaderBundle\Entity\File as EmbeddedFile;
use Vich\UploaderBundle\Mapping\Attribute\Uploadable;
use Vich\UploaderBundle\Mapping\Attribute\UploadableField;

#[ORM\Entity(repositoryClass: NewsRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[Uploadable]
class News implements HistoryAwareEntity
{
    use Traits\EntityHistoryTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: 'text')]
    private ?string $content = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?DateTimeInterface $publishedFrom = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    #[Assert\GreaterThan(propertyPath: 'publishedFrom')]
    private ?DateTimeInterface $publishedTo = null;

    #[UploadableField(mapping: 'news', fileNameProperty: 'image.name', size: 'image.size', mimeType: 'image.mimeType', originalName: 'image.originalName', dimensions: 'image.dimensions')]
    #[Assert\File(maxSize: '10M')]
    #[SafeImageFile]
    private ?File $imageFile = null;

    #[ORM\Embedded(class: 'Vich\UploaderBundle\Entity\File')]
    private EmbeddedFile $image;

    #[ORM\OneToMany(mappedBy: 'news', targetEntity: NewsComment::class, orphanRemoval: true, fetch: 'EXTRA_LAZY')]
    #[ORM\OrderBy(['created' => 'ASC'])]
    private Collection $comments;

    public function __construct()
    {
        $this->image = new EmbeddedFile();
        $this->comments = new ArrayCollection();
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

    /**
     * @return Collection<int, NewsComment>
     */
    public function getComments(): Collection
    {
        return $this->comments;
    }

    public function addComment(NewsComment $comment): self
    {
        if (!$this->comments->contains($comment)) {
            $this->comments->add($comment);
            $comment->setNews($this);
        }

        return $this;
    }

    public function removeComment(NewsComment $comment): self
    {
        if ($this->comments->removeElement($comment) && $comment->getNews() === $this) {
            $comment->setNews(null);
        }

        return $this;
    }
}
