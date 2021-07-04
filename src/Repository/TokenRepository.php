<?php

namespace App\Repository;

use App\Entity\Token;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Ramsey\Uuid\UuidInterface;

/**
 * @method Token|null find($id, $lockMode = null, $lockVersion = null)
 * @method Token|null findOneBy(array $criteria, array $orderBy = null)
 * @method Token[]    findAll()
 * @method Token[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TokenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Token::class);
    }

    public function findToken(string $selector): ?Token
    {
        return $this->findOneBy(['selector' => $selector]);
    }

    public function removeToken(Token $token)
    {
        $this->removeTokens($token->getUserUuid(), $token->getType());
    }

    public function removeTokens(UuidInterface $user, string $type)
    {
        $this->createQueryBuilder('t')
            ->delete()
            ->where('t.userUuid = :user')
            ->andWhere('t.type = :type')
            ->setParameter('user', $user)
            ->setParameter('type', $type)
            ->getQuery()
            ->execute();
    }

    public function countValidToken(UuidInterface $user, string $type)
    {
        return $this->createQueryBuilder('t')
            ->select('count(t.selector)')
            ->where('t.userUuid = :user')
            ->andWhere('t.type = :type')
            ->setParameter('user', $user)
            ->setParameter('type', $type)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function removeExpiredTokens(): int
    {
        $time = new \DateTimeImmutable('-1 week');
        $query = $this->createQueryBuilder('t')
            ->delete()
            ->where('t.expiresAt <= :time')
            ->setParameter('time', $time)
            ->getQuery();

        return $query->execute();
    }
}
