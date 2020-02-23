<?php

namespace App\Repository;

use App\Entity\ContentCategory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method ContentCategory|null find($id, $lockMode = null, $lockVersion = null)
 * @method ContentCategory|null findOneBy(array $criteria, array $orderBy = null)
 * @method ContentCategory[]    findAll()
 * @method ContentCategory[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ContentCategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ContentCategory::class);
    }

    // /**
    //  * @return ContentCategory[] Returns an array of ContentCategory objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('c.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?ContentCategory
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
