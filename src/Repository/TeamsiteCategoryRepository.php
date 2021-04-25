<?php

namespace App\Repository;

use App\Entity\TeamsiteCategory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method TeamsiteCategory|null find($id, $lockMode = null, $lockVersion = null)
 * @method TeamsiteCategory|null findOneBy(array $criteria, array $orderBy = null)
 * @method TeamsiteCategory[]    findAll()
 * @method TeamsiteCategory[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TeamsiteCategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TeamsiteCategory::class);
    }
}
