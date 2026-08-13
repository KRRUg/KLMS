<?php

namespace App\Service;

use App\Entity\Booking;
use App\Entity\BookingResource;
use App\Entity\User;
use App\Exception\BookingException;
use App\Repository\BookingRepository;
use App\Repository\BookingResourceRepository;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\UuidInterface;

class BookingService
{
    private readonly BookingResourceRepository $resourceRepository;
    private readonly BookingRepository $bookingRepository;
    private readonly EntityManagerInterface $em;
    private readonly LoggerInterface $logger;

    public function __construct(
        BookingResourceRepository $resourceRepository,
        BookingRepository $bookingRepository,
        EntityManagerInterface $em,
        LoggerInterface $logger
    ) {
        $this->resourceRepository = $resourceRepository;
        $this->bookingRepository = $bookingRepository;
        $this->em = $em;
        $this->logger = $logger;
    }

    /**
     * @return BookingResource[]
     */
    public function getAllResources(): array
    {
        return $this->resourceRepository->findAllOrderedByPriority();
    }

    /**
     * @return BookingResource[]
     */
    public function getActiveResources(): array
    {
        return $this->resourceRepository->findActiveOrderedByPriority();
    }

    public function createResource(): BookingResource
    {
        return new BookingResource();
    }

    public function saveResource(BookingResource $resource): void
    {
        try {
            $this->em->persist($resource);
            $this->em->flush();

            $this->logger->info('Booking resource saved successfully', [
                'resource_id' => $resource->getId(),
                'name' => $resource->getName(),
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to save booking resource', [
                'error' => $e->getMessage(),
                'name' => $resource->getName(),
            ]);
            throw $e;
        }
    }

    public function deleteResource(BookingResource $resource): void
    {
        try {
            $this->em->remove($resource);
            $this->em->flush();

            $this->logger->info('Booking resource deleted successfully', [
                'resource_id' => $resource->getId(),
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to delete booking resource', [
                'error' => $e->getMessage(),
                'resource_id' => $resource->getId(),
            ]);
            throw $e;
        }
    }

    public function toggleResourceActive(BookingResource $resource): void
    {
        $resource->setActive(!$resource->isActive());
        $this->saveResource($resource);
    }

    /**
     * Builds the list of bookable slots for a resource within an (optional) time window,
     * together with the number of free units per slot.
     *
     * @return array<int, array{start: \DateTimeImmutable, end: \DateTimeImmutable, free: int, total: int}>
     */
    public function getAvailability(BookingResource $resource, ?\DateTimeImmutable $from = null, ?\DateTimeImmutable $to = null, ?\DateTimeImmutable $now = null): array
    {
        $now ??= new \DateTimeImmutable();
        $windowStart = $from !== null && $from > $resource->getAvailableFrom() ? $from : $resource->getAvailableFrom();
        $windowEnd = $to !== null && $to < $resource->getAvailableUntil() ? $to : $resource->getAvailableUntil();

        if ($windowStart < $now) {
            $windowStart = $now;
        }

        $slots = [];
        if ($windowEnd <= $windowStart) {
            return $slots;
        }

        $bufferMinutes = $resource->getBufferMinutes();
        $overlapFrom = $bufferMinutes > 0
            ? $windowStart->modify(sprintf('-%d minutes', $bufferMinutes))
            : $windowStart;
        $relevantBookings = $this->bookingRepository->findActiveOverlappingWindow($resource, $overlapFrom, $windowEnd);
        $duration = new \DateInterval('PT' . $resource->getSlotDurationMinutes() . 'M');
        $step = new \DateInterval('PT' . ($resource->getSlotDurationMinutes() + $bufferMinutes) . 'M');

        $slotStart = $this->alignToGrid($resource, $windowStart);
        while ($slotStart < $windowEnd) {
            $slotEnd = $slotStart->add($duration);
            if ($slotEnd > $windowEnd) {
                break;
            }

            $blockedUnits = $this->collectBlockedUnitsForSlot($relevantBookings, $slotStart, $slotEnd, $bufferMinutes);

            $slots[] = [
                'start' => $slotStart,
                'end' => $slotEnd,
                'total' => $resource->getUnitCount(),
                'free' => max(0, $resource->getUnitCount() - count($blockedUnits)),
            ];

            $slotStart = $slotStart->add($step);
        }

        return $slots;
    }

    /**
     * Books the next free unit of a resource for the given slot start on behalf of a user.
     */
    public function bookSlot(BookingResource $resource, \DateTimeImmutable $start, User $user, ?\DateTimeImmutable $now = null): Booking
    {
        $now ??= new \DateTimeImmutable();

        if (!$resource->isActive()) {
            throw BookingException::resourceInactive();
        }

        if (!$this->isValidSlotStart($resource, $start, $now)) {
            throw BookingException::invalidSlot();
        }

        if ($this->bookingRepository->hasActiveBookingAtStartForUser($resource, $user->getUuid(), $start)) {
            throw BookingException::alreadyBookedSlot();
        }

        $limit = $resource->getMaxBookingsPerUser();
        if ($limit !== null && $this->bookingRepository->countActiveForUser($resource, $user->getUuid(), $now) >= $limit) {
            throw BookingException::limitReached();
        }

        $this->em->beginTransaction();
        try {
            // Serialize concurrent booking attempts on the same resource.
            $this->em->lock($resource, LockMode::PESSIMISTIC_WRITE);

            if ($this->bookingRepository->hasActiveBookingAtStartForUser($resource, $user->getUuid(), $start)) {
                $this->em->rollback();
                throw BookingException::alreadyBookedSlot();
            }

            $duration = new \DateInterval('PT' . $resource->getSlotDurationMinutes() . 'M');
            $end = $start->add($duration);
            $bufferMinutes = $resource->getBufferMinutes();
            $overlapFrom = $bufferMinutes > 0
                ? $start->modify(sprintf('-%d minutes', $bufferMinutes))
                : $start;

            $relevantBookings = $this->bookingRepository->findActiveOverlappingWindow($resource, $overlapFrom, $end);
            $bookedUnits = $this->collectBlockedUnitsForSlot($relevantBookings, $start, $end, $bufferMinutes);
            $unit = null;
            for ($candidate = 1; $candidate <= $resource->getUnitCount(); $candidate++) {
                if (!in_array($candidate, $bookedUnits, true)) {
                    $unit = $candidate;
                    break;
                }
            }

            if ($unit === null) {
                $this->em->rollback();
                throw BookingException::slotFull();
            }

            $booking = new Booking();
            $booking->setResource($resource)
                ->setUserUuid($user->getUuid())
                ->setUnitNumber($unit)
                ->setStartAt($start)
                ->setEndAt($end);

            $this->em->persist($booking);
            $this->em->flush();
            $this->em->commit();

            $this->logger->info('Booking created successfully', [
                'resource_id' => $resource->getId(),
                'unit' => $unit,
                'start' => $start->format(DATE_ATOM),
                'user' => $user->getUuid()->toString(),
            ]);

            return $booking;
        } catch (\Throwable $e) {
            if ($this->em->getConnection()->isTransactionActive()) {
                $this->em->rollback();
            }
            if (!$e instanceof BookingException) {
                $this->logger->error('Failed to create booking', [
                    'error' => $e->getMessage(),
                    'resource_id' => $resource->getId(),
                ]);
            }
            throw $e;
        }
    }

    /**
     * Cancels a booking. Non-admin callers may only cancel their own bookings.
     */
    public function cancelBooking(Booking $booking, User $user, bool $isAdmin = false, ?\DateTimeImmutable $now = null): void
    {
        $reference = $now ?? new \DateTimeImmutable();

        if (!$isAdmin && !$booking->getUserUuid()->equals($user->getUuid())) {
            throw BookingException::notOwner();
        }

        if (!$isAdmin && $booking->getEndAt() <= $reference) {
            throw BookingException::pastBooking();
        }

        if ($booking->isCancelled()) {
            throw BookingException::alreadyCancelled();
        }

        $booking->setCancelledAt($reference);
        $this->em->flush();

        $this->logger->info('Booking cancelled', [
            'booking_id' => $booking->getId(),
            'by_admin' => $isAdmin,
        ]);
    }

    /**
     * @return Booking[]
     */
    public function getUserBookings(User $user, ?\DateTimeImmutable $now = null): array
    {
        return $this->bookingRepository->findActiveByUserOrderedByStart($user->getUuid());
    }

    /**
     * @return Booking[]
     */
    public function getBookingsForResource(BookingResource $resource): array
    {
        return $this->bookingRepository->findByResourceOrderedByStart($resource);
    }

    private function isValidSlotStart(BookingResource $resource, \DateTimeImmutable $start, \DateTimeImmutable $now): bool
    {
        if ($start < $now || $start < $resource->getAvailableFrom()) {
            return false;
        }

        $end = $start->add(new \DateInterval('PT' . $resource->getSlotDurationMinutes() . 'M'));
        if ($end > $resource->getAvailableUntil()) {
            return false;
        }

        return $this->alignToGrid($resource, $start) == $start;
    }

    private function alignToGrid(BookingResource $resource, \DateTimeImmutable $moment): \DateTimeImmutable
    {
        $slotSeconds = ($resource->getSlotDurationMinutes() + $resource->getBufferMinutes()) * 60;
        $offset = $moment->getTimestamp() - $resource->getAvailableFrom()->getTimestamp();
        $alignedOffset = (int) ceil($offset / $slotSeconds) * $slotSeconds;

        return $resource->getAvailableFrom()->add(new \DateInterval('PT' . $alignedOffset . 'S'));
    }

    /**
     * @param Booking[] $bookings
     *
     * @return int[]
     */
    private function collectBlockedUnitsForSlot(array $bookings, \DateTimeImmutable $slotStart, \DateTimeImmutable $slotEnd, int $bufferMinutes): array
    {
        $blocked = [];

        foreach ($bookings as $booking) {
            $blockedUntil = $bufferMinutes > 0
                ? $booking->getEndAt()->add(new \DateInterval('PT' . $bufferMinutes . 'M'))
                : $booking->getEndAt();

            if ($booking->getStartAt() < $slotEnd && $blockedUntil > $slotStart) {
                $blocked[$booking->getUnitNumber()] = true;
            }
        }

        return array_map('intval', array_keys($blocked));
    }
}
