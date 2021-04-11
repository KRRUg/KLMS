<?php

namespace App\Repository;

use App\Entity\Email;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use phpDocumentor\Reflection\Types\Array_;

/**
 * @method Email|null find($id, $lockMode = null, $lockVersion = null)
 * @method Email|null findOneBy(array $criteria, array $orderBy = null)
 * @method Email[]    findAll()
 * @method Email[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EmailRepository extends ServiceEntityRepository
{
	public function __construct(ManagerRegistry $registry)
	{
		parent::__construct($registry, Email::class);
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

    /**
     * @return array with keys 'tbd', 'success', and 'fail' and int values.
     */
    public function countMails(Email $template): array
    {
        $qb = $this->_em->createQueryBuilder()
            ->select('si.success as val, count(si) as cnt')
            ->from('App\Entity\EmailSendingItem', 'si')
            ->innerJoin('si.sending', 's')
            ->where('s.template = :t')
            ->groupBy('si.success')
            ->setParameter('t', $template);
        $qr = $qb->getQuery()->getArrayResult();
        $result = [];
        foreach ($qr as $r) {
            $v = $r['val'];
            $c = $r['cnt'];
            if (is_null($v)){
                $result['tbd'] = $c;
            } elseif ($v === false) {
                $result['fail'] = $c;
            } elseif ($v === true) {
                $result['success'] = $c;
            }
        }
        return $result;
    }

    public function countMailsSuccess(): int
    {

    }

    public function countMailsFail(): int
    {

    }
}
