<?php

namespace App\Repository;

use App\Entity\TextBlock;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\NonUniqueResultException;

/**
 * @method TextBlock|null find($id, $lockMode = null, $lockVersion = null)
 * @method TextBlock|null findOneBy(array $criteria, array $orderBy = null)
 * @method TextBlock[]    findAll()
 * @method TextBlock[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TextBlockRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TextBlock::class);
    }

    public function findByKey(string $key): ?TextBlock
    {
        try {
            return $this->createQueryBuilder('t')
                ->andWhere('t.key = :val')
                ->setParameter('val', $key)
                ->getQuery()
                ->getOneOrNullResult();
        } catch (NonUniqueResultException $e) {
            // this should not happen, as key is a unique index
            return null;
        }
    }
}
