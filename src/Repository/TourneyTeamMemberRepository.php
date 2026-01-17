<?php

namespace App\Repository;

use App\Entity\Tourney;
use App\Entity\TourneyTeamMember;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Ramsey\Uuid\UuidInterface;

/**
 * @extends ServiceEntityRepository<TourneyTeamMember>
 *
 * @method TourneyTeamMember|null find($id, $lockMode = null, $lockVersion = null)
 * @method TourneyTeamMember|null findOneBy(array $criteria, array $orderBy = null)
 * @method TourneyTeamMember[]    findAll()
 * @method TourneyTeamMember[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TourneyTeamMemberRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, private EntityManagerInterface $entityManager)
    {
        parent::__construct($registry, TourneyTeamMember::class);
    }

    public function save(TourneyTeamMember $entity, bool $flush = false): void
    {
        $this->entityManager->persist($entity);

        if ($flush) {
            $this->entityManager->flush();
        }
    }

    public function remove(TourneyTeamMember $entity, bool $flush = false): void
    {
        $this->entityManager->remove($entity);

        if ($flush) {
            $this->entityManager->flush();
        }
    }

    /**
     * @param UuidInterface $uuid
     * @param ?Tourney $tourney If set, limits query to this tourney.
     * @return TourneyTeamMember[]
     */
    public function getTeamMemberByUser(UuidInterface $uuid, ?Tourney $tourney = null): array
    {
        $qb = $this->createQueryBuilder('ttm')
            ->addSelect('tt')
            ->join('ttm.team', 'tt')
            ->where('ttm.gamer = :uuid')
            ->setParameter('uuid', $uuid);

        if (!is_null($tourney)) {
            $qb
                ->andWhere('tt.tourney = :tourney')
                ->setParameter('tourney', $tourney);
        }

        return $qb
            ->getQuery()
            ->getResult();
    }
}
