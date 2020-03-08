<?php

namespace App\DataFixtures;

use App\Entity\NavigationNode;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;

class NavigationFixture extends Fixture
{
    public function load(ObjectManager $manager)
    {
        $root = new NavigationNode();
        $root
            ->setParent($root)
            ->setName("KLMS")
            ->setType("summary");

        $home = new NavigationNode();
        $home
            ->setParent($root)
            ->setName("Home")
            ->setType("content")
            ->setTargetId(1);

        $lan = new NavigationNode();
        $lan
            ->setParent($root)
            ->setName("Lan Party")
            ->setType("summary");

        $lan_facts = new NavigationNode();
        $lan_facts
            ->setParent($lan)
            ->setName("Facts")
            ->setType("content")
            ->setTargetId(2);

        $lan_loc = new NavigationNode();
        $lan_loc
            ->setParent($lan)
            ->setName("Location")
            ->setType("content")
            ->setTargetId(3);

        $manager->persist($root);
        $manager->persist($home);
        $manager->persist($lan);
        $manager->persist($lan_facts);
        $manager->persist($lan_loc);

        $manager->flush();
    }
}
