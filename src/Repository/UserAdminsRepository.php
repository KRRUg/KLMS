<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\UserAdmin;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\NonUniqueResultException;

/**
 * @method UserAdmin|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserAdmin|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserAdmin[]    findAll()
 * @method UserAdmin[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserAdminsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserAdmin::class);
    }

    public function findByUser(User $user) : ?UserAdmin
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

//    public function userHasRight(User $user) : bool
//    {
//        $userAdmin = $this->findByUser($user);
//        if (empty($userAdmin))
//            return false;
//        return $userAdmin->
//    }
}
