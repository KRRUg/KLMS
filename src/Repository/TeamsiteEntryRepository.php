<?php

namespace App\Repository;

use App\Entity\TeamsiteEntry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method TeamsiteEntry|null find($id, $lockMode = null, $lockVersion = null)
 * @method TeamsiteEntry|null findOneBy(array $criteria, array $orderBy = null)
 * @method TeamsiteEntry[]    findAll()
 * @method TeamsiteEntry[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TeamsiteEntryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TeamsiteEntry::class);
    }
}
