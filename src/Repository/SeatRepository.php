<?php

namespace App\Repository;

use App\Entity\Seat;
use App\Entity\SeatKind;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\QueryBuilder;
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
    public function findTakenSeats(): array
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.owner IS NOT NULL')
            ->addOrderBy('s.sector', 'ASC')
            ->addOrderBy('s.seatNumber', 'ASC')
            ->getQuery()
            ->getResult();
    }

    private function createCountQueryBuilder(string $alias = 's'): QueryBuilder
    {
        return $this->createQueryBuilder($alias)
            ->select("count({$alias})")
            ->andWhere("{$alias}.type != :info")
            ->setParameter('info', SeatKind::INFO);
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
            ->andWhere('s.type = :st')
            ->setParameter('st', SeatKind::SEAT)
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

    public function countLockedSeats(): int
    {
        return $this->createCountQueryBuilder('s')
            ->andWhere('s.owner IS NULL')
            ->andWhere('s.type = :st')
            ->setParameter('st', SeatKind::LOCKED)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function getMaxDimension(): array
    {
        $r = $this->createQueryBuilder('s')
            ->select('max(s.posX)')
            ->addSelect('max(s.posY)')
            ->getQuery()
            ->getResult(AbstractQuery::HYDRATE_SCALAR);
        return ['x' => $r[0][1] ?? 0, 'y' => $r[0][2] ?? 0];
    }
}
