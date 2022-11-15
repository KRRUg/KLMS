<?php

namespace App\Service;

use App\Entity\Content;
use App\Exception\ServiceException;
use App\Repository\ContentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class ContentService
{
    private $repo;
    private $em;
    private $logger;

    private $navService;

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
    public function inUse(Content $content)
    {
        return !empty($this->navService->getByContent($content));
    }

    /**
     * @param Content $content Content object to delete
     *
     * @throws ServiceException If deletion fails, bacause Content is still in use
     */
    public function delete(Content $content)
    {
        if ($this->inUse($content)) {
            $this->logger->warning("Can't delete Content {$content->getId()}, still in use ");
            throw new ServiceException(ServiceException::CAUSE_IN_USE);
        }

        $this->logger->info("Deleted Content {$content->getId()} ({$content->getTitle()})");
        $this->em->remove($content);
        $this->em->flush();
    }

    public function save(Content $content)
    {
        $this->logger->info("Create or Update Content {$content->getId()} ({$content->getTitle()})");
        $this->em->persist($content);
        $this->em->flush();
    }
}
