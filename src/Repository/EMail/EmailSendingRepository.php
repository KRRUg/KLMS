<?php

namespace App\Repository\EMail;

use App\Entity\EMail\EmailSending;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method EmailSending|null find($id, $lockMode = null, $lockVersion = null)
 * @method EmailSending|null findOneBy(array $criteria, array $orderBy = null)
 * @method EmailSending[]    findAll()
 * @method EmailSending[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EmailSendingRepository extends ServiceEntityRepository
{
	public function __construct(ManagerRegistry $registry)
	{
		parent::__construct($registry, EmailSending::class);
	}

	/**
	 * @return EmailSending[]
	 */
	public function findNewsletterSendable()
	{
		return $this->createQueryBuilderNewsletterSendable()->getQuery()->execute();
	}

	private function createQueryBuilderNewsletterSendable()
	{
		return $this->createQueryBuilder('emailSending')
		            ->andWhere('emailSending.isInSending = false')
		            ->andWhere('emailSending.isPublished = true')
		            ->andWhere('emailSending.startTime <= :now')
		            ->setParameter('now', new DateTime());

	}

	// /**
	//  * @return EmailSending[] Returns an array of EmailSending objects
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
	public function findOneBySomeField($value): ?EmailSending
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
