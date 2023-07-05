<?php

namespace App\Repository;

use App\Entity\TourneyGame;
use App\Entity\TourneyTeam;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Ramsey\Uuid\UuidInterface;
use function Doctrine\ORM\QueryBuilder;

/**
 * @extends ServiceEntityRepository<TourneyGame>
 *
 * @method TourneyGame|null find($id, $lockMode = null, $lockVersion = null)
 * @method TourneyGame|null findOneBy(array $criteria, array $orderBy = null)
 * @method TourneyGame[]    findAll()
 * @method TourneyGame[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TourneyGameRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TourneyGame::class);
    }

    public function save(TourneyGame $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(TourneyGame $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function findPendingGamesByUser(UuidInterface $user): array
    {
        $qb = $this->createQueryBuilder('g');
        return $qb
            ->join(TourneyTeam::class, 'tt', 'WITH', 'g.teamA = tt.id OR g.teamB = tt.id')
            ->join('tt.members', 'ttm')
            // score is not (fully) set
            ->andWhere($qb->expr()->orX($qb->expr()->isNull('g.scoreA'), $qb->expr()->isNull('g.scoreB')))
            // and both teams are set
            ->andWhere($qb->expr()->andX($qb->expr()->isNotNull('g.teamA'), $qb->expr()->isNotNull('g.teamB')))
            // and one team is user's team
            ->andWhere('ttm.gamer = :uuid')
            ->setParameter('uuid', $user)
            ->getQuery()
            ->getResult();
    }
}
