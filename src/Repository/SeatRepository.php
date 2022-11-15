<?php

namespace App\Repository;

use App\Entity\Seat;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Seat|null find($id, $lockMode = null, $lockVersion = null)
 * @method Seat|null findOneBy(array $criteria, array $orderBy = null)
 * @method Seat[]    findAll()
 * @method Seat[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SeatRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Seat::class);
    }

    /**
     * @return Seat[] Returns an array of Seat objects
     */
    public function findTakenSeats()
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.owner IS NOT NULL')
            ->addOrderBy('s.sector', 'ASC')
            ->addOrderBy('s.seatNumber', 'ASC')
            ->getQuery()
            ->getResult();
    }

    private function createCountQueryBuilder(string $alias = 's')
    {
        return $this->createQueryBuilder($alias)
            ->select("count({$alias})")
            ->andWhere("{$alias}.type != 'information'");
    }

    public function countSeatsTotal(): int
    {
        return $this->createCountQueryBuilder('s')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countFreeSeats(): int
    {
        return $this->createCountQueryBuilder('s')
            ->andWhere('s.owner IS NULL')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countTakenSeats(): int
    {
        return $this->createCountQueryBuilder('s')
            ->andWhere('s.owner IS NOT NULL')
            ->getQuery()
            ->getSingleScalarResult();
    }
}
