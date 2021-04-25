<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\UserGamer;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\NonUniqueResultException;

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
        } catch (NonUniqueResultException $e) {
            // unreachable as we are selecting the primary key
            return null;
        }
    }

    public function findByState(?bool $registered, ?bool $payed, ?bool $seat)
    {
        $qb = $this->createQueryBuilder('u');
        if (!is_null($seat)) {
            // TODO check this magic
            $cmp = $seat ? '>' : '=';
            $qb
                ->leftJoin('u.seats', 'seats')
                ->groupBy('u.guid')
                ->having("count(seats) {$cmp} 0");
        }
        if (!is_null($registered)) {
            $neg = $registered ? "not" : "";
            $qb->andWhere("u.registered is {$neg} null");
        }
        if (!is_null($payed)) {
            $neg = $payed ? "not" : "";
            $qb->andWhere("u.payed is {$neg} null");
        }
        return $qb
            ->getQuery()
            ->getResult();
    }
}
