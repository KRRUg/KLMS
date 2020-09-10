<?php

namespace App\Repository;

use App\Entity\Navigation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\NonUniqueResultException;

/**
 * @method Navigation|null find($id, $lockMode = null, $lockVersion = null)
 * @method Navigation|null findOneBy(array $criteria, array $orderBy = null)
 * @method Navigation[]    findAll()
 * @method Navigation[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class NavigationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Navigation::class);
    }

    public function findOneByName($name): ?Navigation
    {
        try {
            return $this->createQueryBuilder('n')
                ->andWhere('n.name = :name')
                ->setParameter('name', $name)
                ->getQuery()
                ->getOneOrNullResult();
        } catch (NonUniqueResultException $e) {
            // name is unique
            return null;
        }
    }
}
