<?php

namespace App\DataFixtures;

use App\Entity\Content;
use App\Entity\NavigationNodeContent;
use App\Entity\NavigationNodeEmpty;
use App\Entity\NavigationNodeRoot;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;

class ContentFixture extends Fixture
{
    public function load(ObjectManager $manager)
    {
        // Generate Content

        $content[0] = new Content();
        $content[0]->setTitle("Lan is");
        $content[0]->setContent("Lan is wieder einmal.");
        $content[1] = new Content();
        $content[1]->setTitle("FAQ");
        $content[1]->setContent("Wer ist dieser LAN?");
        $content[1]->setAlias("info");
        $content[2] = new Content();
        $content[2]->setTitle("Sitzplan ist online");
        $content[2]->setContent("Wir haben ein paar Sessel gefunden.");

        foreach ($content as $c) {
            $manager->persist($c);
        }


        // Generate Navigation

        $root = new NavigationNodeRoot();

        $home = new NavigationNodeContent();
        $home
            ->setParent($root)
            ->setName("Home")
            ->setContent($content[0]);

        $lan = new NavigationNodeEmpty();
        $lan
            ->setParent($root)
            ->setName("Lan Party");

        $lan_facts = new NavigationNodeContent();
        $lan_facts
            ->setParent($lan)
            ->setName("Facts")
            ->setContent($content[1]);

        $lan_loc = new NavigationNodeContent();
        $lan_loc
            ->setParent($lan)
            ->setName("Location")
            ->setContent($content[2]);

        $manager->persist($root);
        $manager->persist($home);
        $manager->persist($lan);
        $manager->persist($lan_facts);
        $manager->persist($lan_loc);

        $manager->flush();
    }
}
