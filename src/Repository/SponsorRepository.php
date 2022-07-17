<?php

namespace App\Repository;

use App\Entity\Sponsor;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Sponsor>
 *
 * @method Sponsor|null find($id, $lockMode = null, $lockVersion = null)
 * @method Sponsor|null findOneBy(array $criteria, array $orderBy = null)
 * @method Sponsor[]    findAll()
 * @method Sponsor[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SponsorRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Sponsor::class);
    }

//    /**
//     * @throws ORMException
//     * @throws OptimisticLockException
//     */
//    public function add(Sponsor $entity, bool $flush = true): void
//    {
//        $this->_em->persist($entity);
//        if ($flush) {
//            $this->_em->flush();
//        }
//    }
//
//    /**
//     * @throws ORMException
//     * @throws OptimisticLockException
//     */
//    public function remove(Sponsor $entity, bool $flush = true): void
//    {
//        $this->_em->remove($entity);
//        if ($flush) {
//            $this->_em->flush();
//        }
//    }

    public function findOneRandomBy($criteria = [])
    {
        $qb = $this->createQueryBuilder('entity')
            ->select('MIN(entity.id)', 'MAX(entity.id)')
        ;

        foreach ($criteria as $field => $value) {
            $qb
                ->andWhere(sprintf('entity.%s=:%s', $field, $field))
                ->setParameter(':'.$field, $value)
            ;
        }

        $id_limits = $qb
            ->getQuery()
            ->getOneOrNullResult();
        $random_possible_id = rand($id_limits[1], $id_limits[2]);

        return $qb
            ->select('entity')
            ->andWhere('entity.id >= :random_id')
            ->setParameter('random_id', $random_possible_id)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
            ;
    }
}
