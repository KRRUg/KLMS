<?php

namespace App\Service;

use App\Entity\ClanDiscount;
use Doctrine\ORM\EntityManagerInterface;

class ClanDiscountService
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function create(ClanDiscount $clanDiscount): ClanDiscount
    {
        $this->em->persist($clanDiscount);
        $this->em->flush();
        return $clanDiscount;
    }

    public function find(string $id): ?ClanDiscount
    {
        return $this->em->getRepository(ClanDiscount::class)->find($id);
    }

    public function findAll(): array
    {
        return $this->em->getRepository(ClanDiscount::class)->findAll();
    }

    public function remove(ClanDiscount $clanDiscount): void
    {
        $this->em->remove($clanDiscount);
        $this->em->flush();
    }

    public function findByClanIds(array $clanUuids): array
    {
        if (empty($clanUuids)) {
            return [];
        }
        return $this->em->getRepository(ClanDiscount::class)->findBy([
            'clan' => $clanUuids
        ]);
    }
}
