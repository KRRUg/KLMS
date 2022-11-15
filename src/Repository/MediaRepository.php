<?php

namespace App\Repository;

use App\Entity\Media;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Media|null find($id, $lockMode = null, $lockVersion = null)
 * @method Media|null findOneBy(array $criteria, array $orderBy = null)
 * @method Media[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MediaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Media::class);
    }

    /**
     * @return Media|null
     */
    public function findByName(string $name)
    {
        return $this->findOneBy(['fileName' => $name]);
    }

    public function findByDisplayName(string $name)
    {
        return $this->findOneBy(['displayName' => $name]);
    }

    /**
     * @return Media[]
     */
    public function findAll()
    {
        return $this->findBy([], ['displayName' => 'ASC', 'created' => 'ASC']);
    }

    /**
     * @param string $mime Mime prefix to search for (e.g. image or document)
     *
     * @return Media[]
     */
    public function findByMimeClass(string $mime)
    {
        if (empty($mime)) {
            return $this->findAll();
        }

        return $this->createQueryBuilder('m')
            ->where('m.mimeType LIKE :mime')
            ->setParameter('mime', $mime.'/%')
            ->orderBy('m.displayName', 'ASC')
            ->addOrderBy('m.created', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
