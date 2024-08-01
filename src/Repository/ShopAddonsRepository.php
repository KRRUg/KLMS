<?php

namespace App\Repository;

use App\Entity\ShopAddon;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ShopAddon>
 *
 * @method ShopAddon|null find($id, $lockMode = null, $lockVersion = null)
 * @method ShopAddon|null findOneBy(array $criteria, array $orderBy = null)
 * @method ShopAddon[]    findAll()
 * @method ShopAddon[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ShopAddonsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ShopAddon::class);
    }

//    /**
//     * @return ShopAddons[] Returns an array of ShopAddons objects
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

//    public function findOneBySomeField($value): ?ShopAddons
//    {
//        return $this->createQueryBuilder('s')
//            ->andWhere('s.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
