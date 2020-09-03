<?php

namespace App\DataFixtures;

use App\Entity\News;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use joshtronic\LoremIpsum;

class NewsFixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {
        $lipsum = new LoremIpsum();

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

        $news[2] = new News();
        $news[2]->setTitle($lipsum->words(2));
        $news[2]->setContent($lipsum->paragraphs(5));

        $news[3] = new News();
        $news[3]->setTitle("Outdated News");
        $news[3]->setContent("");
        $news[3]->setPublishedFrom(new \DateTime('1990-01-20 17:15'));
        $news[3]->setPublishedTo(new \DateTime('1991-07-18 11:13'));

        $dt = new \DateTime();
        $interval = new \DateInterval('P1D');
        for ($i = 4; $i < 12; $i = $i + 1) {
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
