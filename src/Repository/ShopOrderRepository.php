<?php

namespace App\Repository;

use App\Entity\ShopOrder;
use App\Entity\ShopOrderPositionTicket;
use App\Entity\ShopOrderStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Ramsey\Uuid\UuidInterface;

/**
 * @extends ServiceEntityRepository<ShopOrder>
 *
 * @method ShopOrder|null find($id, $lockMode = null, $lockVersion = null)
 * @method ShopOrder|null findOneBy(array $criteria, array $orderBy = null)
 * @method ShopOrder[]    findAll()
 * @method ShopOrder[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ShopOrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ShopOrder::class);
    }

    private function createQueryFilterBuilder(?UuidInterface $user, ?ShopOrderStatus $status): QueryBuilder
    {
        $qb = $this->createQueryBuilder('o');
        if (!is_null($user)) {
            $qb->andWhere('o.orderer = :orderer');
            $qb->setParameter('orderer', $user);
        }
        if (!is_null($status)) {
            $qb->andWhere('o.status = :status');
            $qb->setParameter('status', $status);
        }
        return $qb;
    }

    public function queryOrders(?UuidInterface $user = null, ?ShopOrderStatus $status = null): array
    {
        return $this->createQueryFilterBuilder($user, $status)
            ->addOrderBy('o.status', 'ASC')
            ->addOrderBy('o.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function countOrders(?UuidInterface $user = null, ?ShopOrderStatus $status = null): int
    {
        return $this->createQueryFilterBuilder($user, $status)
            ->select('count(o)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countOrderedTickets(): int
    {
        return $this->createQueryBuilder('o')
            ->join('o.shopOrderPositions', 'op')
            ->where('op INSTANCE OF '.ShopOrderPositionTicket::class)
            ->andWhere('o.status = :status')
            ->setParameter('status', ShopOrderStatus::Created)
            ->select('count(o)')
            ->getQuery()
            ->getSingleScalarResult();
    }
}
