<?php

namespace App\Repository;

use App\Entity\BookingResource;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<BookingResource>
 */
class BookingResourceRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BookingResource::class);
    }

    /**
     * @return BookingResource[]
     */
    public function findAllOrderedByPriority(): array
    {
        return $this->createQueryBuilder('r')
            ->orderBy('r.priority', 'ASC')
            ->addOrderBy('r.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return BookingResource[]
     */
    public function findActiveOrderedByPriority(): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.active = :active')
            ->setParameter('active', true)
            ->orderBy('r.priority', 'ASC')
            ->addOrderBy('r.id', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
