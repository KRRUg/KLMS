<?php

namespace App\DataFixtures;

use App\Entity\UserAdmin;
use App\Entity\UserGamer;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;

class UserFixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {
        $admin = new UserAdmin();
        $admin->setId("c11ed9b0-e060-4aec-b513-e17c24df2c70");
        $manager->persist($admin);

        $gamer = new UserGamer();
        $gamer->setId("a3ba1298-4aa0-4aa1-5bb2-e18c98fa0980");
        $manager->persist($gamer);
        $manager->flush();
    }
}
