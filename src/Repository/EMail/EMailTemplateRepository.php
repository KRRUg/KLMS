<?php

namespace App\Repository\EMail;

use App\Entity\EMail\EMailTemplate;
use App\Security\User;
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

	public function findAllByRole(User $user): array
	{
		if ($this->hasTemplateAccess($user, null)) {
			$templates = $this->findAll();
		} else {
			$templates = $this->findBy(['ApplicationHook' => null]);
		}
		return $templates;
	}

	public function hasTemplateAccess(User $user, EMailTemplate $template = null): bool
	{

		return $template != null && !$template->isApplicationHooked() || array_key_exists("ROLE_ADMIN_APPLICATION_EMAILS", $user->getRoles()) || $_ENV['APP_ENV'] == 'dev';
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
