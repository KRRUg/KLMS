<?php

namespace App\Repository;

use App\Entity\Navigation;
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

    public function findAllContent()
    {
        return $this->createQueryBuilder('n')
            ->where('n INSTANCE OF App\Entity\NavigationNodeContent')
            ->orderBy('n.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findByNavigation(Navigation $navigation)
    {
        return $this->createQueryBuilder('n')
            ->where('n.navigation = :nav')
            ->setParameter('nav', $navigation)
            ->orderBy('n.lft', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
