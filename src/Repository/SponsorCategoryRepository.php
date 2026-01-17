<?php

namespace App\Repository;

use App\Entity\SponsorCategory;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
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
    public function __construct(ManagerRegistry $registry, private EntityManagerInterface $entityManager)
    {
        parent::__construct($registry, SponsorCategory::class);
    }

    public function add(SponsorCategory $entity, bool $flush = true): void
    {
        $this->entityManager->persist($entity);
        if ($flush) {
            $this->entityManager->flush();
        }
    }

    public function remove(SponsorCategory $entity, bool $flush = true): void
    {
        $this->entityManager->remove($entity);
        if ($flush) {
            $this->entityManager->flush();
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
}
