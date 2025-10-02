<?php

namespace App\Repository;

use App\Entity\ClanDiscount;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ClanDiscount|null find($id, $lockMode = null, $lockVersion = null)
 * @method ClanDiscount|null findOneBy(array $criteria, array $orderBy = null)
 * @method ClanDiscount[]    findAll()
 * @method ClanDiscount[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ClanDiscountRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ClanDiscount::class);
    }

    public function findById($id): ?ClanDiscount
    {
        return $this->findOneBy(['id' => $id]);
    }
}
