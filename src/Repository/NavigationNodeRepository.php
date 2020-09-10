<?php

namespace App\Repository;

use App\Entity\Content;
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
class NavigationNodeRepository extends ServiceEntityRepository
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

    public function findAllContent()
    {
        return $this->createQueryBuilder('n')
            ->where('n INSTANCE OF App\Entity\NavigationNodeContent')
            ->orderBy('n.id', 'ASC')
            ->getQuery()
            ->getResult()
            ;
    }
}
