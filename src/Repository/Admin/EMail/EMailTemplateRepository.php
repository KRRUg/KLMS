<?php

namespace App\Repository\Admin\EMail;

use App\Entity\Admin\EMail\EMailTemplate;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method EMailTemplate|null find($id, $lockMode = null, $lockVersion = null)
 * @method EMailTemplate|null findOneBy(array $criteria, array $orderBy = null)
 * @method EMailTemplate[]    findAll()
 * @method EMailTemplate[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EMailTemplateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EMailTemplate::class);
    }



    // /**
    //  * @return EMailTemplate[] Returns an array of EMailTemplate objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('e.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?EMailTemplate
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
