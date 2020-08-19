<?php


namespace App\Service;

use App\Entity\Image;
use App\Repository\ImageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class ImageService
{
    private $em;
    private $repo;
    private $logger;

    /**
     * ImageService constructor.
     * @param $em
     * @param $repo
     */
    public function __construct(LoggerInterface $logger, EntityManagerInterface $em, ImageRepository $repo)
    {
        $this->em = $em;
        $this->repo = $repo;
        $this->logger = $logger;
    }

    /**
     * @return array All content elements
     */
    public function getAll() : array
    {
        return $this->repo->findAll();
    }

    public function delete(Image $image)
    {
        $this->logger->info("Deleted Image {$image->getId()}");
        $this->em->remove($image);
        $this->em->flush();
    }

    public function save(Image $image)
    {
        if (empty($image) || empty($image->getImageFile()))
            return;

        $this->logger->info("Create Image {$image->getImageFile()->getFilename()} ({$image->getImageFile()->getMimeType()})");
        $this->em->persist($image);
        $this->em->flush();
    }
}