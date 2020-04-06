<?php

namespace App\DataFixtures;

use App\Entity\UserAdmin;
use App\Entity\UserGamer;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use Ramsey\Uuid\Uuid;

class UserFixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {
        $admin = new UserAdmin(Uuid::fromInteger(strval(3)));
        $manager->persist($admin);

        $gamer = new UserGamer(Uuid::fromInteger(strval(4)));
        $gamer->setRegistered(new \DateTime());
        $manager->persist($gamer);
        $gamer = new UserGamer(Uuid::fromInteger(strval(7)));
        $gamer->setRegistered(new \DateTime());
        $manager->persist($gamer);
        $gamer = new UserGamer(Uuid::fromInteger(strval(18)));
        $gamer->setRegistered(new \DateTime());
        $gamer->setPayed(new \DateTime());
        $manager->persist($gamer);

        $manager->flush();
    }
}
