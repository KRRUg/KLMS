<?php


namespace App\Service;


use App\Repository\ContentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class ContentService
{
    private $repo;
    private $em;
    private $logger;

    /**
     * ContentService constructor.
     * @param $repo
     * @param $em
     * @param $logger
     */
    public function __construct(ContentRepository $repo, EntityManagerInterface $em, LoggerInterface $logger)
    {
        $this->repo = $repo;
        $this->em = $em;
        $this->logger = $logger;
    }

    public function getContent() : array
    {
        $allTheContent = $this->repo->findAll();
        $ret = array();
        foreach ($allTheContent as $content) {
            $ret[$content->getId()] = $content;
        }
        return $ret;
    }
}