<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\UserImage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Ramsey\Uuid\UuidInterface;

/**
 * @method UserImage|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserImage|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserImage[]    findAll()
 * @method UserImage[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserImageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserImage::class);
    }

    public function findOneByUser(User $user): ?UserImage
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.uuid = :val')
            ->setParameter('val', $user->getUuid())
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findOneByUuid(UuidInterface $uuid): ?UserImage
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.uuid = :val')
            ->setParameter('val', $uuid)
            ->getQuery()
            ->getOneOrNullResult()
            ;
    }
}
