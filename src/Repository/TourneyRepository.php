<?php

namespace App\Repository;

use App\Entity\Tourney;
use App\Entity\TourneyTeam;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ManagerRegistry;
use Ramsey\Uuid\UuidInterface;

/**
 * @extends ServiceEntityRepository<Tourney>
 *
 * @method Tourney|null find($id, $lockMode = null, $lockVersion = null)
 * @method Tourney|null findOneBy(array $criteria, array $orderBy = null)
 * @method Tourney[]    findAll()
 * @method Tourney[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TourneyRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Tourney::class);
    }

    public function save(Tourney $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Tourney $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @throws NonUniqueResultException
     * @throws NoResultException
     */
    public function getTourneyWithTeams(int $id): Tourney
    {
        return $this->createQueryBuilder('t')
            ->where('t.id = :id')
            ->leftJoin('t.teams', 'tt')
            ->leftJoin('tt.members', 'ttm')
            ->addSelect('tt')
            ->addSelect('ttm')
            ->setParameter('id', $id)
            ->getQuery()
            ->getSingleResult();
    }

    public function getTourneysByUser(UuidInterface $user): array
    {
        $sq = $this->_em->createQueryBuilder()
            ->from(TourneyTeam::class, 'tt')
            ->select('IDENTITY(tt.tourney)')
            ->join('tt.members', 'ttm')
            ->where('ttm.gamer = :uuid')
            ->getDQL();

        $qb = $this->createQueryBuilder('t');
        return $qb
            ->where($qb->expr()->in('t', $sq))
            ->setParameter('uuid', $user)
            ->orderBy('t.order', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
