<?php

namespace App\DataFixtures;

use App\Entity\Content;
use App\Entity\ContentCategory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;

class ContentFixture extends Fixture
{
    public function load(ObjectManager $manager)
    {
        $category[0] = new ContentCategory();
        $category[0]->setName("Allgemeine Infos");
        $category[1] = new ContentCategory();
        $category[1]->setName("Lan Party");
        $category[2] = new ContentCategory();
        $category[2]->setName("Verein");

        foreach ($category as $c) {
            $manager->persist($c);
        }

        $content[0] = new Content();
        $content[0]->setTitle("Lan is");
        $content[0]->setContent("Lan is wieder einmal.");
        $content[0]->setCategory($category[0]);
        $content[1] = new Content();
        $content[1]->setTitle("FAQ");
        $content[1]->setContent("Wer ist dieser LAN?");
        $content[1]->setAlias("info");
        $content[1]->setCategory($category[1]);
        $content[2] = new Content();
        $content[2]->setTitle("Sitzplan ist online");
        $content[2]->setContent("Wir haben ein paar Sessel gefunden.");
        $content[2]->setCategory($category[1]);

        foreach ($content as $c) {
            $manager->persist($c);
        }

        $manager->flush();
    }
}
