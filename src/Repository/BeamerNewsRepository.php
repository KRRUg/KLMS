<?php

namespace App\Repository;

use App\Entity\BeamerNews;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<BeamerNews>
 *
 * @method BeamerNews|null find($id, $lockMode = null, $lockVersion = null)
 * @method BeamerNews|null findOneBy(array $criteria, array $orderBy = null)
 * @method BeamerNews[]    findAll()
 * @method BeamerNews[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BeamerNewsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BeamerNews::class);
    }

    private function createQuery(): QueryBuilder
    {
        return $this->createQueryBuilder('b');
    }

    private function addActiveFilter(QueryBuilder $q): void
    {
        $q->andWhere('b.visible = true');
    }

    private function addOrder(QueryBuilder $q): void
    {
        $q->orderBy('b.created', 'DESC')
          ->addOrderBy('b.id');
    }

    /**
     * @return BeamerNews[]
     */
    public function findAllOrdered(): array
    {
        $q = $this->createQuery();
        $this->addOrder($q);

        return $q->getQuery()->getResult();
    }

    /**
     * @return BeamerNews[]
     */
    public function findActiveOrdered(): array
    {
        $q = $this->createQuery();
        $this->addActiveFilter($q);
        $this->addOrder($q);

        return $q->getQuery()->getResult();
    }
}
