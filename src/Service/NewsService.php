<?php

namespace App\Service;

use App\Entity\News;
use App\Repository\NewsRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Eko\FeedBundle\Item\Writer\RoutedItemInterface;
use Psr\Log\LoggerInterface;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;
use Vich\UploaderBundle\Templating\Helper\UploaderHelperInterface;

class NewsService
{
    private readonly NewsRepository $repo;
    private readonly EntityManagerInterface $em;
    private readonly LoggerInterface $logger;
    private readonly UploaderHelper $uli;

    /**
     * NewsService constructor.
     */
    public function __construct(NewsRepository $repo, EntityManagerInterface $em, LoggerInterface $logger, UploaderHelper $uli)
    {
        $this->repo = $repo;
        $this->em = $em;
        $this->logger = $logger;
        $this->uli = $uli;
    }

    /**
     * @return array All news elements (independent of active status)
     */
    public function getAll(): array
    {
        return $this->repo->findAllOrdered();
    }

    /**
     * @param int|null $from Start of pagination, or null for first
     * @param int|null $to Count of pagination, or null for all elements
     * @return News[]
     */
    public function get(?int $from = null, ?int $to = null): array
    {
        return $this->repo->findActiveOrdered($from, $to);
    }

    public function count(): int
    {
        return $this->repo->countActive();
    }

    public function delete(News $news): void
    {
        $this->logger->info("Deleted News {$news->getId()} ({$news->getTitle()})");
        $this->em->remove($news);
        $this->em->flush();
    }

    public function save(News $news): void
    {
        $this->logger->info("Create or Update News {$news->getId()} ({$news->getTitle()})");
        $this->em->persist($news);
        $this->em->flush();
    }

    public function toFeedElement(News $news): RoutedItemInterface
    {
        $img_url = $this->uli->asset($news);
        return new class ($news, $img_url) implements RoutedItemInterface {
            public function __construct(private readonly News $news, private readonly ?string $img_url){}
            public function getFeedItemTitle(): string { return $this->news->getTitle() ?? ""; }
            public function getFeedItemDescription(): string { return $this->news->getContent() ?? ""; }
            public function getFeedItemRouteName(): string { return 'news_detail'; }
            public function getFeedItemRouteParameters(): array { return ['id' => $this->news->getId()]; }
            public function getFeedItemUrlAnchor(): string { return ''; }
            public function getFeedItemPubDate(): DateTime { return DateTime::createFromInterface($this->news->activeSince()); }
            public function getFeedItemImage(): array { return empty($this->img_url) ? [] : ['type' => $this->news->getImage()->getMimeType(), 'length' => $this->news->getImage()->getSize(), 'value' => $this->img_url]; }
        };
    }
}
