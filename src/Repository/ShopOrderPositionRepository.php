<?php

namespace App\Repository;

use App\Entity\ShopAddon;
use App\Entity\ShopOrderPosition;
use App\Entity\ShopOrderPositionAddon;
use App\Entity\ShopOrderPositionTicket;
use App\Entity\ShopOrderStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Ramsey\Uuid\UuidInterface;

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
    public function __construct(ManagerRegistry $registry, private EntityManagerInterface $entityManager)
    {
        parent::__construct($registry, ShopOrderPosition::class);
    }

    /**
     * @param ShopOrderStatus[] $statusFilter
     * @return int
     */
    public function countOrderedTickets(array $statusFilter): int
    {
        return $this->createQueryBuilder('op')
            ->select('count(op)')
            ->join('op.order', 'o')
            ->where('op INSTANCE OF '.ShopOrderPositionTicket::class)
            ->andWhere('o.status in (:status)')
            ->setParameter('status', $statusFilter)
            ->getQuery()
            ->getSingleScalarResult();
    }

public function countTicketsNotCancelled(): int
    {
        return $this->createQueryBuilder('op')
            ->select('count(op)')
            ->join('op.order', 'o')
            ->where('op INSTANCE OF '.ShopOrderPositionTicket::class)
            ->andWhere('o.status = 1 OR o.status = 9')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @param UuidInterface|null $uuid
     * @param ShopOrderStatus[] $statusFilter
     * @return array [array_id => cnt]
     */
    public function countOrderedAddonsById(?UuidInterface $uuid = null, array $statusFilter = []): array
    {
        $qb = $this->entityManager->createQueryBuilder();
        $q = $qb->select('identity(op.addon) as aid, count(op) as cnt')
            ->from(ShopOrderPositionAddon::class, 'op')
            ->groupBy('op.addon')
            ->join('op.order', 'o');
        if (!empty($statusFilter)) {
           $q
               ->andWhere('o.status in (:status)')
               ->setPaRAMETER('status', $statusFilter);
        }
        if (!is_null($uuid)) {
            $q
                ->andWhere('o.orderer = :uuid')
                ->setParameter('uuid', $uuid);
        }
        return array_column($q->getQuery()->getArrayResult(), 'cnt', 'aid');
    }

    /**
     * @param ShopAddon $addon
     * @param UuidInterface|null $uuid
     * @param ShopOrderStatus[] $statusFilter
     * @return int
     */
    public function countOrderedAddons(ShopAddon $addon, ?UuidInterface $uuid = null, array $statusFilter = []): int
    {
        $qb = $this->entityManager->createQueryBuilder();
        $q = $qb->select('count(op)')
            ->from(ShopOrderPositionAddon::class, 'op')
            ->join('op.order', 'o')
            ->andWhere('op.addon = :addon')
            ->setParameter('addon', $addon);
        if (!empty($statusFilter)) {
            $q
                ->andWhere('o.status in (:status)')
                ->setPaRAMETER('status', $statusFilter);
        }
        if (!is_null($uuid)) {
            $q
                ->andWhere('o.orderer = :uuid')
                ->setParameter('uuid', $uuid);
        }
        return $q->getQuery()->getSingleScalarResult();
    }

    /**
     * @param ShopOrderStatus[] $statusFilter
     * @return ShopOrderPosition[]
     */
    public function getOrderedAddons(array $statusFilter = []): array
    {
        return $this->createQueryBuilder('op')
            ->join('op.order', 'o')
            ->where('op INSTANCE OF '.ShopOrderPositionAddon::class)
            ->andWhere('o.status in (:status)')
            ->setParameter('status', $statusFilter)
            ->getQuery()
            ->getResult();
    }
}
