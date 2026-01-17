<?php

namespace App\Repository;

use App\Entity\GeoData;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<GeoData>
 */
class GeoDataRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry, private EntityManagerInterface $entityManager)
    {
        parent::__construct($registry, GeoData::class);
    }

    public function save(GeoData $entity, bool $flush = false): void
    {
        $this->entityManager->persist($entity);

        if ($flush) {
            $this->entityManager->flush();
        }
    }

    public function findOneByLocation(string $country, string $zip, string $city): ?GeoData
    {
        $qb = $this->createQueryBuilder('g')
            ->andWhere('g.country = :country')
            ->andWhere('g.zip = :zip')
            ->andWhere('g.city = :city')
            ->setParameter('country', $country)
            ->setParameter('zip', $zip)
            ->setParameter('city', $city);

        return $qb
            ->getQuery()
            ->getOneOrNullResult();
    }
}
