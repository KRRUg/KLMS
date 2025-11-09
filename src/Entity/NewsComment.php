<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Traits\EntityHistoryTrait;
use App\Entity\Traits\HistoryAwareEntity;
use App\Repository\NewsCommentRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: NewsCommentRepository::class)]
#[ORM\Table(name: 'news_comment')]
#[ORM\HasLifecycleCallbacks]
class NewsComment implements HistoryAwareEntity
{
    use EntityHistoryTrait;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: News::class, inversedBy: 'comments')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?News $news = null;

    #[ORM\Column(type: 'text')]
    #[Assert\NotBlank(message: 'Bitte gib einen Kommentar ein.')]
    #[Assert\Length(max: 4000, maxMessage: 'Kommentare dürfen maximal {{ limit }} Zeichen lang sein.')]
    private ?string $content = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNews(): ?News
    {
        return $this->news;
    }

    public function setNews(?News $news): self
    {
        $this->news = $news;

        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(?string $content): self
    {
        $this->content = is_null($content) ? null : trim($content);

        return $this;
    }
}
