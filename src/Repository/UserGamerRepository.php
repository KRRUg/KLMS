<?php

namespace App\Repository;

use App\Entity\UserGamer;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method UserGamer|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserGamer|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserGamer[]    findAll()
 * @method UserGamer[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserGamerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserGamer::class);
    }

    // /**
    //  * @return UserGamer[] Returns an array of UserGamer objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('u.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?UserGamer
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
