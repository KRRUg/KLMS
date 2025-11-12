<?php

namespace App\Repository;

use App\Entity\Poll;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Poll>
 */
class PollRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Poll::class);
    }

    public function findActive(DateTimeImmutable $reference): ?Poll
    {
        $qb = $this->createQueryBuilder('poll');
        $qb
            ->andWhere('poll.startAt <= :now')
            ->andWhere($qb->expr()->orX('poll.endAt IS NULL', 'poll.endAt >= :now'))
            ->setParameter('now', $reference)
            ->setMaxResults(1)
            ->orderBy('poll.startAt', 'DESC');

        return $qb->getQuery()->getOneOrNullResult();
    }
}
