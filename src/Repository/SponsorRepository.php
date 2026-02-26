<?php

namespace App\Repository;

use App\Entity\Sponsor;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Sponsor>
 *
 * @method Sponsor|null find($id, $lockMode = null, $lockVersion = null)
 * @method Sponsor|null findOneBy(array $criteria, array $orderBy = null)
 * @method Sponsor[]    findAll()
 * @method Sponsor[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SponsorRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Sponsor::class);
    }

    public function findOneRandomBy($criteria = [])
    {
        $qb = $this->createQueryBuilder('entity')
            ->select('COUNT(entity.id)')
            ->andWhere("entity.isVisible = :isVisible")
            ->setParameter("isVisible", true)
        ;

        foreach ($criteria as $field => $value) {
            $qb
                ->andWhere(sprintf('entity.%s=:%s', $field, $field))
                ->setParameter(':'.$field, $value)
            ;
        }

        $count = $qb
            ->getQuery()
            ->getSingleScalarResult();

        if ($count == 0) {
            return null;
        }

        $offset = rand(0, $count - 1);

        return $qb
            ->select('entity')
            ->setMaxResults(1)
            ->setFirstResult($offset)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findAllVisible()
    {
        return $this->createQueryBuilder('s')
            ->andWhere("s.isVisible = :isVisible")
            ->setParameter("isVisible", true)
            ->addOrderBy('s.sortOrder', 'ASC')
            ->addOrderBy('s.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findAllSorted()
    {
        return $this->createQueryBuilder('s')
            ->addSelect('CASE WHEN s.sortOrder IS NULL THEN 1 ELSE 0 END AS HIDDEN sortOrderNull')
            ->addOrderBy('sortOrderNull', 'ASC')
            ->addOrderBy('s.sortOrder', 'ASC')
            ->addOrderBy('s.name', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
