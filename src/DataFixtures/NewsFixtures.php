<?php

namespace App\DataFixtures;

use App\Entity\News;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;

class NewsFixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {
        // news[1] must be shown before news[0]
        $news[0] = new News();
        $news[0]->setTitle("Wichtig");
        $news[0]->setContent("Eine wichtige Nachricht.");
        $news[0]->setCreated(new \DateTime('2020-07-18 05:05'));
        $news[0]->setPublishedFrom(new \DateTime('2020-07-21 10:00'));

        $news[1] = new News();
        $news[1]->setTitle("Schnitzel");
        $news[1]->setContent("Im Catering gibts Schnitzel!");
        $news[1]->setCreated(new \DateTime('2020-07-19 21:15'));

        $dt = new \DateTime();
        $interval = new \DateInterval('P1D');
        for ($i = 2; $i < 10; $i = $i + 1) {
            $news[$i] = new News();
            $news[$i]->setTitle("News " . $i);
            $news[$i]->setContent("Content of news " . $i);

            $news[$i]->setCreated(clone $dt);
            $dt->sub($interval);
        }

        foreach ($news as $n) {
            $manager->persist($n);
        }
        $manager->flush();
    }
}
