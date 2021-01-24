<?php

namespace App\Repository;

use App\Entity\Teamsite;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Teamsite|null find($id, $lockMode = null, $lockVersion = null)
 * @method Teamsite|null findOneBy(array $criteria, array $orderBy = null)
 * @method Teamsite[]    findAll()
 * @method Teamsite[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TeamsiteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Teamsite::class);
    }
}
