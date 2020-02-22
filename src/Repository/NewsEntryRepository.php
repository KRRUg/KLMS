<?php

namespace App\Repository;

use App\Entity\NewsEntry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method NewsEntry|null find($id, $lockMode = null, $lockVersion = null)
 * @method NewsEntry|null findOneBy(array $criteria, array $orderBy = null)
 * @method NewsEntry[]    findAll()
 * @method NewsEntry[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class NewsEntryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NewsEntry::class);
    }

    // /**
    //  * @return NewsEntry[] Returns an array of NewsEntry objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('n.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?NewsEntry
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
