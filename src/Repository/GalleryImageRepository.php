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
        $images = $this->findBy([], ['createdAt' => 'DESC']);
        $grouped = [];

        foreach ($images as $image) {
            $event = $image->getEvent();
            if (!isset($grouped[$event])) {
                $grouped[$event] = [];
            }
            $grouped[$event][] = $image;
        }
    
        ksort($grouped);

        return $grouped;
    }

    /**
     * @return GalleryImage[] Returns images for a specific event
     */
    public function findByEvent(string $event): array
    {
        return $this->createQueryBuilder('g')
            ->andWhere('g.event = :event')
            ->setParameter('event', $event)
            ->orderBy('g.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * @return string[] Returns unique event names
     */
    public function findEvents(): array
    {
        $result = $this->createQueryBuilder('g')
            ->select('DISTINCT g.event')
            ->orderBy('g.event', 'ASC')
            ->getQuery()
            ->getScalarResult();

        return array_column($result, 'event');
    }
}