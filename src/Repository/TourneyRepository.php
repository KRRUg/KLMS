<?php

namespace App\Repository;

use App\Entity\Tourney;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

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
}
