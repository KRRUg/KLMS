<?php

namespace App\Repository\Admin\EMail;

use App\Entity\Admin\EMail\EmailSending;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method EmailSending|null find($id, $lockMode = null, $lockVersion = null)
 * @method EmailSending|null findOneBy(array $criteria, array $orderBy = null)
 * @method EmailSending[]    findAll()
 * @method EmailSending[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EMailSendingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EmailSending::class);
    }

    // /**
    //  * @return emailQueue[] Returns an array of emailQueue objects
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
    public function findOneBySomeField($value): ?emailQueue
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
