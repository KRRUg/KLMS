<?php


namespace App\Service;


use App\Entity\News;
use App\Repository\NewsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class NewsService
{
    private $repo;
    private $em;
    private $logger;

    /**
     * NewsService constructor.
     * @param $repo
     * @param $em
     * @param $logger
     */
    public function __construct(NewsRepository $repo, EntityManagerInterface $em, LoggerInterface $logger)
    {
        $this->repo = $repo;
        $this->em = $em;
        $this->logger = $logger;
    }

    /**
     * @return array All content elements
     */
    public function getAll() : array
    {
        return $this->repo->findAllOrdered();
    }

    public function get(int $from = 0, int $to = 6)
    {
        return $this->repo->findActiveOrdered($from, $to);
    }

    public function count() : int
    {
        return $this->repo->countActive();
    }

    public function delete(News $news)
    {
        $this->logger->info("Deleted News {$news->getId()} ({$news->getTitle()})");
        $this->em->remove($news);
        $this->em->flush();
    }

    public function save(News $news)
    {
        $this->logger->info("Create or Update News {$news->getId()} ({$news->getTitle()})");
        $this->em->persist($news);
        $this->em->flush();
    }
}