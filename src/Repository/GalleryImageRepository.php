<?php

namespace App\Repository;

use App\Entity\GalleryImage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<GalleryImage>
 */
class GalleryImageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GalleryImage::class);
    }

    public function save(GalleryImage $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(GalleryImage $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @return GalleryImage[] Returns an array of GalleryImage objects grouped by event
     */
    public function findAllGroupedByEvent(): array
    {
        $images = $this->createQueryBuilder('g')
            ->leftJoin('g.galleryEvent', 'e')
            ->orderBy('e.priority', 'ASC')
            ->addOrderBy('g.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
        
        $grouped = [];
        foreach ($images as $image) {
            $event = $image->getGalleryEvent();
            $eventKey = $event ? $event->getName() : 'Ohne Event';
            if (!isset($grouped[$eventKey])) {
                $grouped[$eventKey] = [];
            }
            $grouped[$eventKey][] = $image;
        }

        return $grouped;
    }

    /**
     * @return GalleryImage[] Returns images for a specific event
     */
    public function findByEvent(string $eventName): array
    {
        if ($eventName === 'Ohne Event') {
            return $this->createQueryBuilder('g')
                ->where('g.galleryEvent IS NULL')
                ->orderBy('g.createdAt', 'DESC')
                ->getQuery()
                ->getResult();
        }
        
        return $this->createQueryBuilder('g')
            ->leftJoin('g.galleryEvent', 'e')
            ->where('e.name = :eventName')
            ->setParameter('eventName', $eventName)
            ->orderBy('g.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array Returns event entities with their names
     */
    public function findEvents(): array
    {
        $result = $this->createQueryBuilder('g')
            ->select('DISTINCT e.name')
            ->leftJoin('g.galleryEvent', 'e')
            ->where('e.name IS NOT NULL')
            ->orderBy('e.priority', 'ASC')
            ->getQuery()
            ->getScalarResult();

        $events = array_column($result, 'name');
        
        // Add "Ohne Event" if there are images without events
        $hasImagesWithoutEvent = $this->createQueryBuilder('g')
            ->select('COUNT(g.uuid)')
            ->where('g.galleryEvent IS NULL')
            ->getQuery()
            ->getSingleScalarResult() > 0;
            
        if ($hasImagesWithoutEvent) {
            $events[] = 'Ohne Event';
        }
        
        return $events;
    }
}