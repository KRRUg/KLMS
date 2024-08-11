<?php

namespace App\DataFixtures;

use App\Entity\UserAdmin;
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

        $manager->flush();
    }
}
