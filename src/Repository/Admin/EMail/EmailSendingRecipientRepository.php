<?php

namespace App\Repository\Admin\EMail;

use App\Entity\Admin\EMail\EmailSendingRecipient;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method EmailSendingRecipient|null find($id, $lockMode = null, $lockVersion = null)
 * @method EmailSendingRecipient|null findOneBy(array $criteria, array $orderBy = null)
 * @method EmailSendingRecipient[]    findAll()
 * @method EmailSendingRecipient[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EmailSendingRecipientRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EmailSendingRecipient::class);
    }

    // /**
    //  * @return EmailSendingRecipient[] Returns an array of EmailSendingRecipient objects
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
    public function findOneBySomeField($value): ?EmailSendingRecipient
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
