<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\News;
use App\Entity\NewsComment;
use App\Entity\User;
use App\Repository\NewsCommentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;

class NewsCommentService
{
    public function __construct(
        private readonly NewsCommentRepository $repository,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @return NewsComment[]
     */
    public function getForNews(News $news): array
    {
        return $this->repository->findForNews($news);
    }

    public function countForNews(News $news): int
    {
        return $this->repository->countForNews($news);
    }

    public function addComment(News $news, NewsComment $comment, User $author): NewsComment
    {
        $uuid = $author->getUuid();
        if (null === $uuid) {
            throw new RuntimeException('Aktueller Benutzer besitzt keine UUID.');
        }

        $comment->setNews($news);
        $comment->setAuthorId($uuid);
        $comment->setModifierId($uuid);

        $this->entityManager->persist($comment);
        $this->entityManager->flush();

        $this->logger->info('News comment created', [
            'newsId' => $news->getId(),
            'commentId' => $comment->getId(),
            'authorUuid' => $uuid->toString(),
        ]);

        return $comment;
    }
}
