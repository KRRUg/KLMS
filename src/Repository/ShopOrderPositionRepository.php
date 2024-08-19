<?php

namespace App\Repository;

use App\Entity\ShopOrderPosition;
use App\Entity\ShopOrderPositionAddon;
use App\Entity\ShopOrderPositionTicket;
use App\Entity\ShopOrderStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ShopOrderPosition>
 *
 * @method ShopOrderPosition|null find($id, $lockMode = null, $lockVersion = null)
 * @method ShopOrderPosition|null findOneBy(array $criteria, array $orderBy = null)
 * @method ShopOrderPosition[]    findAll()
 * @method ShopOrderPosition[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ShopOrderPositionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ShopOrderPosition::class);
    }

    public function countOrderedTickets(ShopOrderStatus $status = ShopOrderStatus::Created): int
    {
        return $this->createQueryBuilder('op')
            ->select('count(op)')
            ->join('op.order', 'o')
            ->where('op INSTANCE OF '.ShopOrderPositionTicket::class)
            ->andWhere('o.status = :status')
            ->setParameter('status', $status)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @param ShopOrderStatus $status
     * @return ShopOrderPosition[]
     */
    public function getOrderedAddons(ShopOrderStatus $status = ShopOrderStatus::Paid): array
    {
        return $this->createQueryBuilder('op')
            ->join('op.order', 'o')
            ->where('op INSTANCE OF '.ShopOrderPositionAddon::class)
            ->andWhere('o.status = :status')
            ->setParameter('status', $status)
            ->getQuery()
            ->getResult();
    }
}
