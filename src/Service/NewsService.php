<?php

namespace App\Service;

use App\Entity\News;
use App\Repository\NewsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class NewsService
{
    private readonly NewsRepository $repo;
    private readonly EntityManagerInterface $em;
    private readonly LoggerInterface $logger;

    /**
     * NewsService constructor.
     */
    public function __construct(NewsRepository $repo, EntityManagerInterface $em, LoggerInterface $logger)
    {
        $this->repo = $repo;
        $this->em = $em;
        $this->logger = $logger;
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
}
