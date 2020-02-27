<?php

namespace App\Repository\Admin\EMail;

use App\Entity\Admin\EMail\EmailSendingTask;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method EmailSendingTask|null find($id, $lockMode = null, $lockVersion = null)
 * @method EmailSendingTask|null findOneBy(array $criteria, array $orderBy = null)
 * @method EmailSendingTask[]    findAll()
 * @method EmailSendingTask[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EmailSendingTaskRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EmailSendingTask::class);
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
