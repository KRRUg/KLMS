<?php

namespace App\Repository;

use App\Entity\EmailSending;
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
}
