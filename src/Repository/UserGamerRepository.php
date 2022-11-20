<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\UserGamer;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method UserGamer|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserGamer|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserGamer[]    findAll()
 * @method UserGamer[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserGamerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserGamer::class);
    }

    public function findByUser(User $user): ?UserGamer
    {
        try {
            return $this->createQueryBuilder('u')
                ->andWhere('u.uuid = :uuid')
                ->setParameter('uuid', $user->getUuid())
                ->getQuery()
                ->getOneOrNullResult();
        } catch (NonUniqueResultException) {
            // unreachable as we are selecting the primary key
            return null;
        }
    }

    private function createQueryFilterBuilder(?bool $registered, ?bool $paid, ?bool $seat, string $alias = 'u'): QueryBuilder
    {
        $qb = $this->createQueryBuilder($alias);
        if (!is_null($seat)) {
            $cmp = $seat ? '>' : '=';
            $qb
                ->leftJoin("{$alias}.seats", 'seats')
                ->groupBy("{$alias}.guid")
                ->having("count(seats) {$cmp} 0");
        }
        if (!is_null($registered)) {
            $neg = $registered ? 'not' : '';
            $qb->andWhere("{$alias}.registered is {$neg} null");
        }
        if (!is_null($paid)) {
            $neg = $paid ? 'not' : '';
            $qb->andWhere("{$alias}.paid is {$neg} null");
        }

        return $qb;
    }

    public function findByState(?bool $registered, ?bool $paid, ?bool $seat)
    {
        return $this->createQueryFilterBuilder($registered, $paid, $seat)
            ->getQuery()
            ->getResult();
    }

    public function countByState(?bool $registered, ?bool $paid, ?bool $seat): int
    {
        return $this->createQueryFilterBuilder($registered, $paid, $seat, 'u')
            ->select('count(u)')
            ->getQuery()
            ->getSingleScalarResult();
    }
}
