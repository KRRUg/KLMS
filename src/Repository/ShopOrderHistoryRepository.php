<?php

namespace App\Repository;

use App\Entity\ShopOrderHistory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ShopOrderHistory>
 *
 * @method ShopOrderHistory|null find($id, $lockMode = null, $lockVersion = null)
 * @method ShopOrderHistory|null findOneBy(array $criteria, array $orderBy = null)
 * @method ShopOrderHistory[]    findAll()
 * @method ShopOrderHistory[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ShopOrderHistoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ShopOrderHistory::class);
    }
}
