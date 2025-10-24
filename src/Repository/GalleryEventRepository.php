<?php

namespace App\Repository;

use App\Entity\GalleryEvent;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<GalleryEvent>
 *
 * @method GalleryEvent|null find($id, $lockMode = null, $lockVersion = null)
 * @method GalleryEvent|null findOneBy(array $criteria, array $orderBy = null)
 * @method GalleryEvent[]    findAll()
 * @method GalleryEvent[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class GalleryEventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GalleryEvent::class);
    }

    public function save(GalleryEvent $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(GalleryEvent $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Find all events ordered by priority
     */
    public function findAllOrderedByPriority(): array
    {
        return $this->createQueryBuilder('ge')
            ->orderBy('ge.priority', 'ASC')
            ->addOrderBy('ge.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Find events that have gallery images
     */
    public function findEventsWithImages(): array
    {
        return $this->createQueryBuilder('ge')
            ->innerJoin('ge.galleryImages', 'gi')
            ->orderBy('ge.priority', 'ASC')
            ->addOrderBy('ge.name', 'ASC')
            ->groupBy('ge.id')
            ->getQuery()
            ->getResult();
    }
}