<?php

namespace App\Repository;

use App\Entity\Booking;
use App\Entity\BookingResource;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Ramsey\Uuid\UuidInterface;

/**
 * @extends ServiceEntityRepository<Booking>
 */
class BookingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Booking::class);
    }

    /**
     * Unit numbers that are already booked (and not cancelled) for the given resource and exact slot start.
     *
     * @return int[]
     */
    public function findActiveUnitNumbers(BookingResource $resource, \DateTimeImmutable $start): array
    {
        $result = $this->createQueryBuilder('b')
            ->select('b.unitNumber')
            ->andWhere('b.resource = :resource')
            ->andWhere('b.startAt = :start')
            ->andWhere('b.cancelledAt IS NULL')
            ->setParameter('resource', $resource)
            ->setParameter('start', $start)
            ->getQuery()
            ->getScalarResult();

        return array_map(static fn(array $row) => (int) $row['unitNumber'], $result);
    }

    /**
     * Number of active bookings booked by a user for a resource, ordered from now on.
     */
    public function countActiveForUser(BookingResource $resource, UuidInterface $userUuid, \DateTimeImmutable $reference): int
    {
        return (int) $this->createQueryBuilder('b')
            ->select('COUNT(b.id)')
            ->andWhere('b.resource = :resource')
            ->andWhere('b.userUuid = :userUuid')
            ->andWhere('b.cancelledAt IS NULL')
            ->andWhere('b.endAt >= :reference')
            ->setParameter('resource', $resource)
            ->setParameter('userUuid', $userUuid)
            ->setParameter('reference', $reference)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function hasActiveBookingAtStartForUser(BookingResource $resource, UuidInterface $userUuid, \DateTimeImmutable $start): bool
    {
        $count = (int) $this->createQueryBuilder('b')
            ->select('COUNT(b.id)')
            ->andWhere('b.resource = :resource')
            ->andWhere('b.userUuid = :userUuid')
            ->andWhere('b.startAt = :start')
            ->andWhere('b.cancelledAt IS NULL')
            ->setParameter('resource', $resource)
            ->setParameter('userUuid', $userUuid)
            ->setParameter('start', $start)
            ->getQuery()
            ->getSingleScalarResult();

        return $count > 0;
    }

    /**
     * Counts active bookings per slot start (as timestamp) for a resource within a time range.
     *
     * @return array<int, int> map of slot start timestamp to number of active bookings
     */
    public function countActiveBySlotStart(BookingResource $resource, \DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        $rows = $this->createQueryBuilder('b')
            ->select('b.startAt AS startAt, COUNT(b.id) AS bookedCount')
            ->andWhere('b.resource = :resource')
            ->andWhere('b.startAt >= :from')
            ->andWhere('b.startAt < :to')
            ->andWhere('b.cancelledAt IS NULL')
            ->setParameter('resource', $resource)
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->groupBy('b.startAt')
            ->getQuery()
            ->getResult();

        $counts = [];
        foreach ($rows as $row) {
            /** @var \DateTimeImmutable $startAt */
            $startAt = $row['startAt'];
            $counts[$startAt->getTimestamp()] = (int) $row['bookedCount'];
        }

        return $counts;
    }

    /**
     * @return Booking[]
     */
    public function findByResourceOrderedByStart(BookingResource $resource): array
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.resource = :resource')
            ->setParameter('resource', $resource)
            ->orderBy('b.startAt', 'ASC')
            ->addOrderBy('b.unitNumber', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Active, upcoming bookings of a user across all resources.
     *
     * @return Booking[]
     */
    public function findActiveUpcomingByUser(UuidInterface $userUuid, \DateTimeImmutable $reference): array
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.userUuid = :userUuid')
            ->andWhere('b.cancelledAt IS NULL')
            ->andWhere('b.endAt >= :reference')
            ->setParameter('userUuid', $userUuid)
            ->setParameter('reference', $reference)
            ->orderBy('b.startAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Active bookings of a user across all resources, including past bookings.
     *
     * @return Booking[]
     */
    public function findActiveByUserOrderedByStart(UuidInterface $userUuid): array
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.userUuid = :userUuid')
            ->andWhere('b.cancelledAt IS NULL')
            ->setParameter('userUuid', $userUuid)
            ->orderBy('b.startAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Active bookings overlapping the given time window.
     *
     * @return Booking[]
     */
    public function findActiveOverlappingWindow(BookingResource $resource, \DateTimeImmutable $from, \DateTimeImmutable $to): array
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.resource = :resource')
            ->andWhere('b.cancelledAt IS NULL')
            ->andWhere('b.startAt < :to')
            ->andWhere('b.endAt > :from')
            ->setParameter('resource', $resource)
            ->setParameter('from', $from)
            ->setParameter('to', $to)
            ->orderBy('b.startAt', 'ASC')
            ->addOrderBy('b.unitNumber', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
