<?php

namespace App\Service;

use App\Entity\Content;
use App\Exception\ServiceException;
use App\Repository\ContentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class ContentService implements WipeInterface
{
    private readonly ContentRepository $repo;
    private readonly EntityManagerInterface $em;
    private readonly LoggerInterface $logger;
    private readonly NavigationService $navService;

    /**
     * ContentService constructor.
     */
    public function __construct(ContentRepository $repo, EntityManagerInterface $em, LoggerInterface $logger, NavigationService $navService)
    {
        $this->repo = $repo;
        $this->em = $em;
        $this->logger = $logger;
        $this->navService = $navService;
    }

    /**
     * @return array All content elements
     */
    public function getAll(): array
    {
        $allTheContent = $this->repo->findAll();
        $ret = [];
        foreach ($allTheContent as $content) {
            $ret[$content->getId()] = $content;
        }

        return $ret;
    }

    /**
     * @param Content $content Content object to check
     *
     * @return bool If content is in use
     */
    public function inUse(Content $content): bool
    {
        return !empty($this->navService->getByContent($content));
    }

    /**
     * @param Content $content Content object to delete
     *
     * @throws ServiceException If deletion fails, bacause Content is still in use
     */
    public function delete(Content $content): void
    {
        if ($this->inUse($content)) {
            $this->logger->warning("Can't delete Content {$content->getId()}, still in use ");
            throw new ServiceException(ServiceException::CAUSE_IN_USE);
        }

        $this->logger->info("Deleted Content {$content->getId()} ({$content->getTitle()})");
        $this->em->remove($content);
        $this->em->flush();
    }

    public function save(Content $content): void
    {
        $this->em->persist($content);
        $this->em->flush();
        $this->logger->info("Create or Update Content {$content->getId()} ({$content->getTitle()})");
    }

    public function reset(): void
    {
        foreach ($this->repo->findAll() as $content) {
            $this->em->remove($content);
        }
        $this->em->flush();
    }

    public function resetBefore(): array
    {
        return [NavigationService::class];
    }
}
