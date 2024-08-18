<?php

namespace App\Repository;

use App\Entity\Ticket;
use App\Service\TicketState;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Ramsey\Uuid\UuidInterface;

/**
 * @extends ServiceEntityRepository<Ticket>
 *
 * @method Ticket|null find($id, $lockMode = null, $lockVersion = null)
 * @method Ticket|null findOneBy(array $criteria, array $orderBy = null)
 * @method Ticket[]    findAll()
 * @method Ticket[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TicketRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Ticket::class);
    }

    public function findOneByRedeemer(UuidInterface $uuid): ?Ticket
    {
        return $this->findOneBy(['redeemer' => $uuid]);
    }

    public function findOneByCode(string $code): ?Ticket
    {
        return $this->findOneBy(['code' => $code]);
    }

    public function countRedeemed(): int
    {
        return $this->createQueryBuilder('t')
            ->select('count(t)')
            ->where('t.redeemedAt IS NOT NULL')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countPunched(): int
    {
        return $this->createQueryBuilder('t')
            ->select('count(t)')
            ->where('t.punchedAt IS NOT NULL')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countInvalid(): int
    {
        return $this->createQueryBuilder('t')
            ->select('count(t)')
            ->orWhere('t.redeemedAt IS NULL AND t.punchedAt IS NOT NULL')
            ->orWhere('t.redeemedAt IS NULL AND t.redeemer IS NOT NULL')
            ->orWhere('t.redeemedAt IS NOT NULL AND t.redeemer IS NULL')
            ->orWhere('t.createdAt > t.redeemedAt')
            ->orWhere('t.redeemedAt > t.punchedAt')
            ->orWhere('t.createdAt > t.punchedAt')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Get all Ticket which are in the state or in a later state
     * @return Ticket[]
     */
    public function findByState(TicketState $state): array
    {
        $qb = $this->createQueryBuilder('t');
        switch ($state) {
            case TicketState::PUNCHED:
                $qb->andWhere('t.punchedAt IS NOT NULL');
                break;
            case TicketState::REDEEMED:
                $qb->andWhere('t.redeemedAt IS NOT NULL');
                break;
            case TicketState::NEW:
                break;
        }
        return $qb->getQuery()
            ->getResult();
    }
}
