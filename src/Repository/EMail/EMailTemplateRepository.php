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

	public function hasTemplateAccess(User $user, EMailTemplate $template = null): bool
	{

		return  $template != null && !$template->isApplicationHooked() || array_key_exists("ROLE_ADMIN_APPLICATION_EMAILS", $user->getRoles()) || $_ENV['APP_ENV'] == 'dev';
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
