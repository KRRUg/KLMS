<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\News;
use App\Entity\NewsComment;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<NewsComment>
 */
class NewsCommentRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NewsComment::class);
    }

    /**
     * @return NewsComment[]
     */
    public function findForNews(News $news): array
    {
        return $this->createQueryBuilder('comment')
            ->andWhere('comment.news = :news')
            ->setParameter('news', $news)
            ->orderBy('comment.created', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function countForNews(News $news): int
    {
        return (int) $this->createQueryBuilder('comment')
            ->select('COUNT(comment.id)')
            ->andWhere('comment.news = :news')
            ->setParameter('news', $news)
            ->getQuery()
            ->getSingleScalarResult();
    }
}
