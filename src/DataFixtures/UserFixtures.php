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

        $admin = new UserAdmin(Uuid::fromInteger(strval(7)));
        $admin->setPermissions(['ADMIN_TOURNEY']);
        $manager->persist($admin);


        $gamer = new UserGamer(Uuid::fromInteger(strval(17)));
        $gamer->setRegistered(new DateTime());
        $manager->persist($gamer);

        $gamer = new UserGamer(Uuid::fromInteger(strval(18)));
        $gamer->setRegistered(new DateTime());
        $gamer->setPaid(new DateTime());
        $manager->persist($gamer);

        $gamer = new UserGamer(Uuid::fromInteger(strval(14)));
        $gamer->setRegistered(new DateTime());
        $gamer->setPaid(new DateTime());
        $gamer->setCheckedIn(new DateTime());
        $manager->persist($gamer);

        for ($i = 1; $i <= 10; $i++) {
            $gamer = new UserGamer(Uuid::fromInteger(strval($i)));
            $gamer->setRegistered(new DateTime());
            $gamer->setPaid(new DateTime());
            $gamer->setCheckedIn(new DateTime());
            $this->setReference('gamer-'.$i, $gamer);
            $manager->persist($gamer);
        }

        $manager->flush();
    }
}
