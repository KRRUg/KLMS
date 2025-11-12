<?php

namespace App\Repository;

use App\Entity\Poll;
use App\Entity\PollVote;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PollVote>
 */
class PollVoteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PollVote::class);
    }

    public function findExistingVote(Poll $poll, ?string $userUuid, ?string $fingerprintHash): ?PollVote
    {
        if ($userUuid) {
            $vote = $this->createQueryBuilder('vote')
                ->andWhere('vote.poll = :poll')
                ->andWhere('vote.userUuid = :userUuid')
                ->setParameter('poll', $poll)
                ->setParameter('userUuid', $userUuid)
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();

            if ($vote) {
                return $vote;
            }
        }

        if ($fingerprintHash) {
            return $this->createQueryBuilder('vote')
                ->andWhere('vote.poll = :poll')
                ->andWhere('vote.fingerprintHash = :fingerprint')
                ->setParameter('poll', $poll)
                ->setParameter('fingerprint', $fingerprintHash)
                ->setMaxResults(1)
                ->getQuery()
                ->getOneOrNullResult();
        }

        return null;
    }

    /**
     * @return array<int, int>
     */
    public function getVoteCounts(Poll $poll): array
    {
        $rows = $this->createQueryBuilder('vote')
            ->select('IDENTITY(vote.option) AS optionId', 'COUNT(vote.id) AS voteCount')
            ->andWhere('vote.poll = :poll')
            ->groupBy('vote.option')
            ->setParameter('poll', $poll)
            ->getQuery()
            ->getArrayResult();

        $counts = [];
        foreach ($rows as $row) {
            $counts[(int) $row['optionId']] = (int) $row['voteCount'];
        }

        return $counts;
    }

    public function countForPoll(Poll $poll): int
    {
        return (int) $this->createQueryBuilder('vote')
            ->select('COUNT(vote.id)')
            ->andWhere('vote.poll = :poll')
            ->setParameter('poll', $poll)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
