<?php

namespace App\DataFixtures;

use App\Entity\News;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;

class NewsFixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {

        $news[0] = new News();
        $news[0]->setTitle("Wichtig");
        $news[0]->setContent("Eine wichtige Nachricht.");

        $news[1] = new News();
        $news[1]->setTitle("Schnitzel");
        $news[1]->setContent("Im Catering gibts Schnitzel!");

        foreach ($news as $n) {
            $manager->persist($n);
        }
        $manager->flush();
    }
}
