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
    public function findActive($offset = null, $count = null)
    {
        $p = $this->createQueryBuilder('n')
            ->andWhere('(n.publishedTo is null) or (n.publishedTo >= :now)')
            ->andWhere('(n.publishedFrom is null) or (n.publishedFrom <= :now)')
            ->setParameter('now', new \DateTime('now'))
            ->orderBy('n.created');
        if (is_int($offset))
            $p->setFirstResult($offset);
        if (is_int($count))
            $p->setMaxResults($count);
        return $p
            ->getQuery()
            ->getResult()
        ;
    }
}
