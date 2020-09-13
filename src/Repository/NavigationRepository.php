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

    /**
     * @param string[] $names Names to look for
     * @return Navigation[] Found navigations
     */
    public function findByNames(array $names): array
    {
        return $this->createQueryBuilder('n')
            ->where('n.name in (:names)')
            ->setParameter('names', $names)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param string $name Name to look for
     * @return Navigation|null Navigation if exists, null if not found
     */
    public function findOneByName(string $name): ?Navigation
    {
        try {
            return $this->createQueryBuilder('n')
                ->where('n.name = :name')
                ->setParameter('name', $name)
                ->getQuery()
                ->getOneOrNullResult();
        } catch (NonUniqueResultException $e) {
            // name is unique
            return null;
        }
    }
}
