<?php

namespace App\Repository;

use App\Entity\SponsorCategory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SponsorCategory>
 *
 * @method SponsorCategory|null find($id, $lockMode = null, $lockVersion = null)
 * @method SponsorCategory|null findOneBy(array $criteria, array $orderBy = null)
 * @method SponsorCategory[]    findAll()
 * @method SponsorCategory[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SponsorCategoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SponsorCategory::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(SponsorCategory $entity, bool $flush = true): void
    {
        $this->_em->persist($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function remove(SponsorCategory $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    public function findAllWithVisibleSponsors(): array {
        return $this->createQueryBuilder('sc')
            ->addSelect('s')
            ->leftJoin('sc.sponsors', 's')
            ->andWhere("s.isVisible = :isVisible")
            ->orWhere("s.isVisible is null")
            ->setParameter("isVisible", true)
            ->getQuery()
            ->getResult()
            ;
    }

    // /**
    //  * @return SponsorCategory[] Returns an array of SponsorCategory objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('s.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?SponsorCategory
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
