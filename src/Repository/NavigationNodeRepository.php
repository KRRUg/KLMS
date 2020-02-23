<?php

namespace App\Repository;

use App\Entity\NavigationNode;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method NavigationNode|null find($id, $lockMode = null, $lockVersion = null)
 * @method NavigationNode|null findOneBy(array $criteria, array $orderBy = null)
 * @method NavigationNode[]    findAll()
 * @method NavigationNode[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class NavigationNodeRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NavigationNode::class);
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
