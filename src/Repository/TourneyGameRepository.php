<?php

namespace App\Repository;

use App\Entity\TourneyGame;
use App\Entity\TourneyTeam;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Ramsey\Uuid\UuidInterface;

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
        // TODO join the two fields with custom join with or

        // all teams of user
        $sq1 = $this->_em->createQueryBuilder()
            ->from(TourneyTeam::class, 'tt1')
            ->select('tt1')
            ->join('tt1.members', 'ttm1')
            ->where('ttm1.gamer = :uuid')
            ->getDQL();
        $sq2 = $this->_em->createQueryBuilder()
            ->from(TourneyTeam::class, 'tt2')
            ->select('tt2')
            ->join('tt2.members', 'ttm2')
            ->where('ttm2.gamer = :uuid')
            ->getDQL();

        $qb = $this->createQueryBuilder('g');
        return $qb
            // score is not (fully) set
            ->andWhere($qb->expr()->orX($qb->expr()->isNull('g.scoreA'), $qb->expr()->isNull('g.scoreB')))
            // and both teams are set
            ->andWhere($qb->expr()->andX($qb->expr()->isNotNull('g.teamA'), $qb->expr()->isNotNull('g.teamB')))
            // and one team is user's team
            ->andWhere($qb->expr()->orX($qb->expr()->in('g.teamA', $sq1), $qb->expr()->in('g.teamB', $sq2)))
            //->andWhere($qb->expr()->in('g.teamA', $sq))
            ->setParameter('uuid', $user)
            ->getQuery()
            ->getArrayResult()
        ;
    }
}
