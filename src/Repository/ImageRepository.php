<?php

namespace App\Repository;

use App\Entity\Image;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method Image|null find($id, $lockMode = null, $lockVersion = null)
 * @method Image|null findOneBy(array $criteria, array $orderBy = null)
 * @method Image[]    findAll()
 * @method Image[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ImageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Image::class);
    }

    /**
     * @param $idOrName
     * @return Image|null
     */
    public function findByNameAndId($idOrName)
    {
        if (empty($idOrName))
            return null;
        if (is_numeric($idOrName))
            return $this->find(intval($idOrName));
        $img = $this->findOneBy(['image.originalName' => $idOrName]);
        if (!empty($img))
            return $img;
        return $this->findOneBy(['name' => $idOrName]);
    }
}