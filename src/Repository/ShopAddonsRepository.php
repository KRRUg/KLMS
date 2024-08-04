<?php

namespace App\Repository;

use App\Entity\ShopAddon;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ShopAddon>
 *
 * @method ShopAddon|null find($id, $lockMode = null, $lockVersion = null)
 * @method ShopAddon|null findOneBy(array $criteria, array $orderBy = null)
 * @method ShopAddon[]    findAll()
 * @method ShopAddon[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ShopAddonsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ShopAddon::class);
    }

    public function findActive(): array
    {
        return $this->findBy(['active' => true], ['index' => 'ASC']);
    }
}
