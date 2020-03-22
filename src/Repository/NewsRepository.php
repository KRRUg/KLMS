<?php

namespace App\Repository;

use App\Entity\News;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method News|null find($id, $lockMode = null, $lockVersion = null)
 * @method News|null findOneBy(array $criteria, array $orderBy = null)
 * @method News[]    findAll()
 * @method News[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class NewsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, News::class);
    }

    /**
     * @return News[] Returns an array of News objects that are active
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
}
