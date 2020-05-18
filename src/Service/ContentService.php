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

    // TODO merge with Navigation service?

    /**
     * ContentService constructor.
     * @param $repo
     * @param $em
     * @param $logger
     */
    public function __construct(ContentRepository $repo, EntityManagerInterface $em, LoggerInterface $logger, NavService $navService)
    {
        $this->repo = $repo;
        $this->em = $em;
        $this->logger = $logger;
        $this->navService = $navService;
    }

    /**
     * @return array All content elements
     */
    public function getAll() : array
    {
        $allTheContent = $this->repo->findAll();
        $ret = array();
        foreach ($allTheContent as $content) {
            $ret[$content->getId()] = $content;
        }
        return $ret;
    }

    /**
     * @param Content $content Content object to delete
     * @throws ServiceException If deletion fails, bacause Content is still in use
     */
    public function delete(Content $content)
    {
        if (!empty($this->navService->getByContent($content))) {
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