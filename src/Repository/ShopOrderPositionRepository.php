<?php

namespace App\Repository;

use App\Entity\ShopOrderPosition;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ShopOrderPosition>
 *
 * @method ShopOrderPosition|null find($id, $lockMode = null, $lockVersion = null)
 * @method ShopOrderPosition|null findOneBy(array $criteria, array $orderBy = null)
 * @method ShopOrderPosition[]    findAll()
 * @method ShopOrderPosition[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ShopOrderPositionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ShopOrderPosition::class);
    }

//    /**
//     * @return ShopOrderPosition[] Returns an array of ShopOrderPosition objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('s')
//            ->andWhere('s.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('s.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?ShopOrderPosition
//    {
//        return $this->createQueryBuilder('s')
//            ->andWhere('s.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
