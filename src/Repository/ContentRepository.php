<?php

namespace App\Repository;

use App\Entity\Content;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Content|null find($id, $lockMode = null, $lockVersion = null)
 * @method Content|null findOneBy(array $criteria, array $orderBy = null)
 * @method Content[]    findAll()
 * @method Content[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ContentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Content::class);
    }

    /**
     * @param string $alias Alias to search for
     */
    public function findByAlias(string $alias): ?Content
    {
        return $this->findOneBy(['alias' => $alias]);
    }

    public function findById(int $id): ?Content
    {
        return $this->findOneBy(['id' => $id]);
    }

    public function findBySlug(string $slug): ?Content
    {
        if (empty($slug)) {
            return null;
        }

        return $this->findOneBy(['alias' => $slug]);
    }
}
