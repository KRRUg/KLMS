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
        return $this->countOrderedTickets(ShopOrderStatus::STATUS_NOT_DEAD);
    }

    /**
     * @param UuidInterface|null $uuid
     * @param ShopOrderStatus[] $statusFilter
     * @return array [addon_id => cnt]
     */
    public function countOrderedAddonsById(?UuidInterface $uuid = null, array $statusFilter = []): array
    {
        $q = $this->entityManager->createQueryBuilder()
            ->select('identity(op.addon) as aid, count(op) as cnt')
            ->from(ShopOrderPositionAddon::class, 'op')
            ->groupBy('op.addon')
            ->join('op.order', 'o');
        if (!empty($statusFilter)) {
            $q->andWhere('o.status in (:status)')
              ->setParameter('status', $statusFilter);
        }
        if (!is_null($uuid)) {
            $q->andWhere('o.orderer = :uuid')
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
        $q = $this->entityManager->createQueryBuilder()
            ->select('count(op)')
            ->from(ShopOrderPositionAddon::class, 'op')
            ->join('op.order', 'o')
            ->andWhere('op.addon = :addon')
            ->setParameter('addon', $addon);
        if (!empty($statusFilter)) {
            $q->andWhere('o.status in (:status)')
              ->setParameter('status', $statusFilter);
        }
        if (!is_null($uuid)) {
            $q->andWhere('o.orderer = :uuid')
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

    /**
     * Builds a base query for addons that consume seats, filtered by order status.
     *
     * @param ShopOrderStatus[] $statuses  Empty array defaults to STATUS_NOT_DEAD.
     */
    private function consumingAddonsQuery(string $select, array $statuses): mixed
    {
        return $this->entityManager->createQueryBuilder()
            ->select($select)
            ->from(ShopOrderPositionAddon::class, 'op')
            ->join('op.addon', 'a')
            ->join('op.order', 'o')
            ->andWhere('a.consumesSeats IS NOT NULL')
            ->andWhere('o.status IN (:statuses)')
            ->setParameter('statuses', empty($statuses) ? ShopOrderStatus::STATUS_NOT_DEAD : $statuses)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Sum of all consumed seats (consumesSeats × units ordered) for non-cancelled addon orders.
     *
     * @param ShopOrderStatus[] $statuses  Empty array defaults to STATUS_NOT_DEAD.
     */
    public function countConsumedSeats(array $statuses = []): int
    {
        return (int) $this->consumingAddonsQuery('SUM(a.consumesSeats)', $statuses);
    }

    /**
     * Count of ordered units of seat-consuming addons (= ticket slots used).
     * Each unit counts as 1 ticket slot regardless of the consumesSeats value.
     *
     * @param ShopOrderStatus[] $statuses  Empty array defaults to STATUS_NOT_DEAD.
     */
    public function countConsumedTickets(array $statuses = []): int
    {
        return (int) $this->consumingAddonsQuery('COUNT(op)', $statuses);
    }

    /**
     * Returns the top $limit most-ordered addons (by name) with their count.
     * Addons deleted (SET NULL) are excluded.
     * Only positions from orders with the given statuses are counted.
     *
     * @param ShopOrderStatus[] $statusFilter  Defaults to STATUS_ACTIVE (paid only).
     * @return array<array{name: string, cnt: int}>
     */
    public function topAddonItems(int $limit = 5, array $statusFilter = []): array
    {
        if (empty($statusFilter)) {
            $statusFilter = ShopOrderStatus::STATUS_ACTIVE;
        }

        return $this->entityManager->createQueryBuilder()
            ->select('sa.name, COUNT(posa) as cnt')
            ->from(ShopOrderPositionAddon::class, 'posa')
            ->join('posa.addon', 'sa')
            ->join('posa.order', 'o')
            ->andWhere('posa.addon IS NOT NULL')
            ->andWhere('o.status IN (:status)')
            ->setParameter('status', $statusFilter)
            ->groupBy('sa.id, sa.name')
            ->orderBy('cnt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
