<?php

namespace App\Repository;

use App\Entity\TourneyTeam;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ManagerRegistry;
use Ramsey\Uuid\UuidInterface;

/**
 * @extends ServiceEntityRepository<TourneyTeam>
 *
 * @method TourneyTeam|null find($id, $lockMode = null, $lockVersion = null)
 * @method TourneyTeam|null findOneBy(array $criteria, array $orderBy = null)
 * @method TourneyTeam[]    findAll()
 * @method TourneyTeam[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TourneyTeamRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TourneyTeam::class);
    }

    public function save(TourneyTeam $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(TourneyTeam $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function userInTeam(TourneyTeam $team, UuidInterface $uuid, ?bool $accepted = null): bool
    {
        $qb = $this->createQueryBuilder('tt')
            ->select('count(ttm)')
            ->where('tt = :team')
            ->join('tt.members', 'ttm')
            ->andWhere('ttm.gamer = :uuid')
            ->setParameter('team', $team)
            ->setParameter('uuid', $uuid);

        if (!is_null($accepted)){
            $qb
                ->andWhere('ttm.accepted = :acc')
                ->setParameter('acc', $accepted);
        }
        return $qb->getQuery()->getSingleScalarResult() > 0;
    }
}
