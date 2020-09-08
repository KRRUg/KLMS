<?php


namespace App\Service;

use App\Entity\Media;
use App\Repository\MediaRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class MediaService
{
    private $em;
    private $repo;
    private $logger;

    /**
     * ImageService constructor.
     * @param $em
     * @param $repo
     */
    public function __construct(LoggerInterface $logger, EntityManagerInterface $em, MediaRepository $repo)
    {
        $this->em = $em;
        $this->repo = $repo;
        $this->logger = $logger;
    }

    /**
     * @return Media[] All content elements
     */
    public function getAll() : array
    {
        return $this->repo->findAll();
    }

    /**
     * @return Media[]
     */
    public function getImages(): array
    {
        return $this->repo->findByMimeClass('image');
    }

    public function getDocuments(): array
    {
        return $this->repo->findByMimeClass('application');
    }

    public function delete(Media $image)
    {
        $this->logger->info("Deleted Image {$image->getId()}");
        $this->em->remove($image);
        $this->em->flush();
    }

    public function save(Media $media)
    {
        if (empty($media) || empty($media->getMediaFile()))
            return;

        $this->logger->info("Create Media {$media->getMediaFile()->getFilename()} ({$media->getMediaFile()->getMimeType()})");
        $this->em->persist($media);
        $this->em->flush();
    }
}