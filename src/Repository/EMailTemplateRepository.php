<?php

namespace App\Repository;

use App\Entity\EMailTemplate;
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
        return $this->createQueryBuilder('emailTemplate')
            ->leftJoin('emailTemplate.emailSending', 'emailSending')
            ->andwhere('emailSending is null')
            ->orderBy('emailTemplate.created', 'DESC')
            ->getQuery()
            ->execute();
    }
}
