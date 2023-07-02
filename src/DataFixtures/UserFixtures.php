<?php

namespace App\DataFixtures;

use App\Entity\UserAdmin;
use App\Entity\UserGamer;
use DateTime;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Ramsey\Uuid\Uuid;

class UserFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $admin = new UserAdmin(Uuid::fromInteger(strval(3)));
        $admin->setPermissions(['ADMIN_NEWS']);
        $manager->persist($admin);

        $gamer = new UserGamer(Uuid::fromInteger(strval(4)));
        $gamer->setRegistered(new DateTime());
        $manager->persist($gamer);

        $gamer = new UserGamer(Uuid::fromInteger(strval(7)));
        $gamer->setRegistered(new DateTime());
        $manager->persist($gamer);

        $gamer = new UserGamer(Uuid::fromInteger(strval(18)));
        $gamer->setRegistered(new DateTime());
        $gamer->setPaid(new DateTime());
        $manager->persist($gamer);

        $gamer = new UserGamer(Uuid::fromInteger(strval(2)));
        $gamer->setRegistered(new DateTime());
        $gamer->setPaid(new DateTime());
        $gamer->setCheckedIn(new DateTime());
        $manager->persist($gamer);

        $gamer = new UserGamer(Uuid::fromInteger(strval(14)));
        $gamer->setRegistered(new DateTime());
        $gamer->setPaid(new DateTime());
        $gamer->setCheckedIn(new DateTime());
        $manager->persist($gamer);

        $manager->flush();
    }
}
