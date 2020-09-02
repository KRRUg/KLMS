<?php

namespace App\Repository;

use App\Entity\News;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
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

    private function getActiveQuery()
    {
        return $this->createQueryBuilder('n')
            ->andWhere('(n.publishedTo is null) or (n.publishedTo >= :now)')
            ->andWhere('(n.publishedFrom is null) or (n.publishedFrom <= :now)')
            ->setParameter('now', new \DateTime('now'));
    }

    /**
     * @return News[] Returns an array of News objects that are active
     */
    public function findActive($offset = null, $count = null)
    {
        $p = $this->getActiveQuery()
            ->addSelect('CASE WHEN n.publishedFrom IS NULL THEN n.created ELSE n.publishedFrom END AS HIDDEN sort_order')
            ->orderBy('sort_order', 'DESC');
        if (is_int($offset))
            $p->setFirstResult($offset);
        if (is_int($count))
            $p->setMaxResults($count);
        return $p
            ->getQuery()
            ->getResult()
        ;
    }

    public function countActive() : int
    {
        try {
            return $this->getActiveQuery()
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
