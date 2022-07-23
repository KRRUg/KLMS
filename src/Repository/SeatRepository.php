<?php

namespace App\Repository;

use App\Entity\Seat;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

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

    // /**
    //  * @return Seat[] Returns an array of Seat objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('s.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Seat
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
