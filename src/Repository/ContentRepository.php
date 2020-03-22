<?php

namespace App\Repository;

use App\Entity\Content;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method Content|null find($id, $lockMode = null, $lockVersion = null)
 * @method Content|null findOneBy(array $criteria, array $orderBy = null)
 * @method Content[]    findAll()
 * @method Content[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ContentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Content::class);
    }

    /**
     * @return Content[] Returns an array of Content objects that are active
     * @throws \Exception
     */
    public function findActive()
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.publishedTo >= :now')
            ->andWhere('n.publishedFrom <= :now')
            ->setParameter('now', new \DateTime('now'))
            ->orderBy('n.publishedFrom')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @param $alias Alias to search for
     * @return mixed
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function findByAlias($alias)
    {
        return $this->createQueryBuilder('n')
            ->andWhere('n.alias = :alias')
            ->setParameter('alias', $alias)
            ->getQuery()
            ->getOneOrNullResult()
            ;
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
