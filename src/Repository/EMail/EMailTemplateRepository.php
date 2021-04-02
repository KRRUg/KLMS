<?php

namespace App\Repository\EMail;

use App\Entity\EMail\EMailTemplate;
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

	public function findAllTemplatesWithoutSendings()
	{
		return $this->createQueryBuilderTemplatesWithoutSendings()
		            ->orderBy('emailTemplate.created', 'DESC')
		            ->getQuery()
		            ->execute();
	}

	private function createQueryBuilderTemplatesWithoutSendings()
	{
		return $this->createQueryBuilderNewsletterTemplates()
		            ->leftJoin('emailTemplate.emailSending', 'emailSending')
		            ->andwhere('emailSending is null');

	}

	private function createQueryBuilderNewsletterTemplates()
	{
		return $this->createQueryBuilder('emailTemplate')
		            ->andWhere('emailTemplate.applicationHook is null or emailTemplate.applicationHook = \'\'');

	}

	/**
	 * @return EMailTemplate[]
	 */

	public function findAllWithApplicationHook(): array
	{
		return $this->createQueryBuilderApplicationHookTemplates()
		            ->orderBy('emailTemplate.name', 'ASC')
		            ->getQuery()
		            ->execute();
	}

	private function createQueryBuilderApplicationHookTemplates()
	{
		return $this->createQueryBuilder('emailTemplate')
		            ->andWhere('emailTemplate.applicationHook is not null and emailTemplate.applicationHook > \'\'');

	}
}
