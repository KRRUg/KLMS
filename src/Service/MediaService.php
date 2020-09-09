<?php


namespace App\Service;

use App\Entity\Media;
use App\Exception\ServiceException;
use App\Repository\MediaRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;

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

    private function getDisplayName(Media $media): ?string
    {
        // When saving a media object, the displayName property is not set by Vich yet.
        $name = $media->getDisplayName();
        if (empty($name)) {
            $file = $media->getMediaFile();
            if ($file instanceof UploadedFile) {
                $name = $file->getClientOriginalName();
            }
        }
        return $name;
    }

    public function save(Media $media)
    {
        if (empty($media) || empty($media->getMediaFile())) {
            throw new ServiceException(ServiceException::CAUSE_EMPTY);
        }

        $name = $this->getDisplayName($media);

        // Ensure that the display name is unique.
        if (!empty($this->repo->findByDisplayName($name))) {
            throw new ServiceException(ServiceException::CAUSE_EXIST);
        }

        $this->logger->info("Create Media {$name} ({$media->getMediaFile()->getMimeType()})");
        $this->em->persist($media);
        $this->em->flush();
    }
}