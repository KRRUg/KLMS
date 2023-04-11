<?php

namespace App\Repository;

use App\Entity\TourneyTeamMember;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TourneyTeamMember>
 *
 * @method TourneyTeamMember|null find($id, $lockMode = null, $lockVersion = null)
 * @method TourneyTeamMember|null findOneBy(array $criteria, array $orderBy = null)
 * @method TourneyTeamMember[]    findAll()
 * @method TourneyTeamMember[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TourneyTeamMemberRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TourneyTeamMember::class);
    }

    public function save(TourneyTeamMember $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(TourneyTeamMember $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

//    /**
//     * @return TourneyTeamMember[] Returns an array of TourneyTeamMember objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('t')
//            ->andWhere('t.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('t.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?TourneyTeamMember
//    {
//        return $this->createQueryBuilder('t')
//            ->andWhere('t.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
