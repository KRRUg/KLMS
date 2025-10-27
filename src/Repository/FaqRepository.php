<?php

namespace App\Repository;

use App\Entity\Faq;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Faq>
 */
class FaqRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Faq::class);
    }

    /**
     * Find all FAQs ordered by priority
     *
     * @return Faq[]
     */
    public function findAllOrderedByPriority(): array
    {
        return $this->createQueryBuilder('f')
            ->orderBy('f.priority', 'ASC')
            ->addOrderBy('f.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find active FAQs ordered by priority
     *
     * @return Faq[]
     */
    public function findActiveOrderedByPriority(): array
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.active = :active')
            ->setParameter('active', true)
            ->orderBy('f.priority', 'ASC')
            ->addOrderBy('f.id', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Search FAQs by question text
     *
     * @param string $searchTerm
     * @return Faq[]
     */
    public function searchByQuestion(string $searchTerm): array
    {
        return $this->createQueryBuilder('f')
            ->andWhere('f.active = :active')
            ->andWhere('LOWER(f.question) LIKE LOWER(:search) OR LOWER(f.answer) LIKE LOWER(:search)')
            ->setParameter('active', true)
            ->setParameter('search', '%' . $searchTerm . '%')
            ->orderBy('f.priority', 'ASC')
            ->addOrderBy('f.id', 'ASC')
            ->getQuery()
            ->getResult();
    }
}