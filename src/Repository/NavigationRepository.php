<?php

namespace App\Repository;

use App\Entity\NavigationNode;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\NonUniqueResultException;

/**
 * @method NavigationNode|null find($id, $lockMode = null, $lockVersion = null)
 * @method NavigationNode|null findOneBy(array $criteria, array $orderBy = null)
 * @method NavigationNode[]    findAll()
 * @method NavigationNode[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class NavigationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NavigationNode::class);
    }

    public function getRoot() : NavigationNode
    {
        $root = $this->createQueryBuilder('n')
            ->andWhere('n instance of App\Entity\NavigationNodeRoot')
            ->getQuery()
            ->getOneOrNullResult();

        if ($root) {
            return $root;
        } else {
            throw new \Exception('Root element not found');
        }
    }

    public function getRootChildren()
    {
        return $this
            ->getRoot()
            ->getChildNodes()
            ->filter(function ($node) { return $node->getParent() !== $node; });
    }

    // /**
    //  * @return NavigationNode[] Returns an array of NavigationNode objects
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
    public function findOneBySomeField($value): ?NavigationNode
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
