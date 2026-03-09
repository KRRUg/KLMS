<?php

namespace App\Service;

use App\Entity\BeamerNews;
use App\Repository\BeamerNewsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class BeamerNewsService
{
    private readonly BeamerNewsRepository $repo;
    private readonly EntityManagerInterface $em;
    private readonly LoggerInterface $logger;

    public function __construct(BeamerNewsRepository $repo, EntityManagerInterface $em, LoggerInterface $logger)
    {
        $this->repo = $repo;
        $this->em = $em;
        $this->logger = $logger;
    }

    /**
     * @return BeamerNews[] All beamer news (independent of active status)
     */
    public function getAll(): array
    {
        return $this->repo->findAllOrdered();
    }

    /**
     * @return BeamerNews[] Only active and visible beamer news
     */
    public function getActive(): array
    {
        return $this->repo->findActiveOrdered();
    }

    public function delete(BeamerNews $beamerNews): void
    {
        $this->logger->info("Deleted BeamerNews {$beamerNews->getId()} ({$beamerNews->getTitle()})");
        $this->em->remove($beamerNews);
        $this->em->flush();
    }

    public function save(BeamerNews $beamerNews): void
    {
        $this->logger->info("Create or Update BeamerNews {$beamerNews->getId()} ({$beamerNews->getTitle()})");
        $this->em->persist($beamerNews);
        $this->em->flush();
    }
}
