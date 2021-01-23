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

    public function findByUser(User $user)
    {
        try {
            return $this->createQueryBuilder('u')
                ->andWhere('u.guid = :uuid')
                ->setParameter('uuid', $user->getUuid())
                ->getQuery()
                ->getOneOrNullResult();
        } catch (NonUniqueResultException $e) {
            // unreachable as we are selecting the primary key
            return null;
        }
    }
}
