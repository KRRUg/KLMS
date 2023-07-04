<?php

namespace App\Repository;

use App\Entity\Tourney;
use App\Entity\TourneyEntrySinglePlayer;
use App\Entity\TourneyEntryTeam;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
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

    public function getTourneysByUser(UuidInterface $user): array
    {
        $sq1 = $this->getEntityManager()->createQueryBuilder()
            ->from(TourneyEntrySinglePlayer::class, 'esp')
            ->select('IDENTITY(esp.tourney)')
            ->where('esp.gamer = :uuid')
            ->getDQL();

        $sq2 = $this->_em->createQueryBuilder()
            ->from(TourneyEntryTeam::class, 'tet')
            ->select('IDENTITY(tet.tourney)')
            ->join('tet.members', 'ttm')
            ->where('ttm.gamer = :uuid')
            ->getDQL();

        $qb = $this->createQueryBuilder('t');
        return $qb
            ->orWhere($qb->expr()->in('t', $sq1))
            ->orWhere($qb->expr()->in('t', $sq2))
            ->setParameter('uuid', $user)
            ->orderBy('t.order', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
