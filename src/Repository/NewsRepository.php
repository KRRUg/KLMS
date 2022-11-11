<?php

namespace App\Repository;

use App\Entity\News;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\ORM\QueryBuilder;
use Psr\Log\LoggerInterface;

/**
 * @method News|null find($id, $lockMode = null, $lockVersion = null)
 * @method News|null findOneBy(array $criteria, array $orderBy = null)
 * @method News[]    findAll()
 * @method News[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class NewsRepository extends ServiceEntityRepository
{
    private $logger;

    public function __construct(ManagerRegistry $registry, LoggerInterface $logger)
    {
        parent::__construct($registry, News::class);
        $this->logger = $logger;
    }

    private function createQuery()
    {
        return $this->createQueryBuilder('n');
    }

    private function addActiveFilter(QueryBuilder $q)
    {
        $q->andWhere('(n.publishedTo is null) or (n.publishedTo >= :now)')
          ->andWhere('(n.publishedFrom is null) or (n.publishedFrom <= :now)')
          ->setParameter('now', new \DateTime('now'));
    }

    private function addOrder(QueryBuilder $q)
    {
        $q->addSelect('CASE WHEN n.publishedFrom IS NULL THEN n.created ELSE n.publishedFrom END AS HIDDEN sort_order')
          ->orderBy('sort_order', 'DESC')
          ->addOrderBy('n.id');
    }

    /**
     * @return News[] Returns an array of News objects
     */
    public function findAllOrdered()
    {
        $q = $this->createQuery();
        $this->addOrder($q);
        return $q
            ->getQuery()
            ->getResult();
    }

    /**
     * @return News[] Returns an array of News objects that are active
     */
    public function findActiveOrdered($offset = null, $count = null)
    {
        $q = $this->createQuery();
        $this->addActiveFilter($q);
        $this->addOrder($q);
        if (is_int($offset))
            $q->setFirstResult($offset);
        if (is_int($count))
            $q->setMaxResults($count);
        return $q
            ->getQuery()
            ->getResult();
    }

    public function countActive() : int
    {
        try {
            $q = $this->createQuery();
            $this->addActiveFilter($q);
            return $q
                ->select('count(n.id)')
                ->getQuery()
                ->getSingleScalarResult();
        } catch (NoResultException | NonUniqueResultException $e) {
            // should not happen
            $this->logger->emergency('News Count query returned something odd.');
            return 0;
        }
    }
}
