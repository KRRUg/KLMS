<?php

namespace App\Repository;

use App\Entity\Media;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method Media|null find($id, $lockMode = null, $lockVersion = null)
 * @method Media|null findOneBy(array $criteria, array $orderBy = null)
 * @method Media[]    findAll()
 * @method Media[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MediaRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Media::class);
    }

    /**
     * @param $idOrName
     * @return Media|null
     */
    public function findByNameAndId($idOrName)
    {
        // TODO replace with generated name
        if (empty($idOrName))
            return null;
        if (is_numeric($idOrName))
            return $this->find(intval($idOrName));
        $img = $this->findOneBy(['image.originalName' => $idOrName]);
        if (!empty($img))
            return $img;
        return $this->findOneBy(['name' => $idOrName]);
    }

    public function findByName($name)
    {

    }
}