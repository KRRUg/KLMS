<?php


namespace App\Service;


use App\Entity\Sponsor;
use App\Repository\SponsorRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class SponsorService
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
    public function __construct(SponsorRepository $repo, EntityManagerInterface $em, LoggerInterface $logger)
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
        return $this->repo->findAll();
    }

    public function count() : int
    {
        return $this->repo->count([]);
    }

    public function delete(Sponsor $sponsor)
    {
        $this->logger->info("Deleted Sponsor {$sponsor->getId()} ({$sponsor->getName()})");
        $this->em->remove($sponsor);
        $this->em->flush();
    }

    public function save(Sponsor $sponsor)
    {
        $this->logger->info("Create or Update Sponsor {$sponsor->getId()} ({$sponsor->getName()})");
        $this->em->persist($sponsor);
        $this->em->flush();
    }
}