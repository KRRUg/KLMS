<?php

namespace App\DataFixtures;

use App\Entity\Content;
use App\Entity\NavigationNodeContent;
use App\Entity\NavigationNodeEmpty;
use App\Entity\NavigationNodeGeneric;
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
        $content[2]->setTitle("Das Kulturzentrum");
        $content[2]->setContent("Wir haben ein paar Sessel gefunden.");
        $content[3] = new Content();
        $content[3]->setTitle("Catering");
        $content[3]->setContent("Es gibt was zu essen");
        $content[4] = new Content();
        $content[4]->setTitle("Netzerk und Internet");
        $content[4]->setContent("Haben wir auch.");


        foreach ($content as $c) {
            $manager->persist($c);
        }


        // Generate Navigation

        $root = new NavigationNodeRoot();

        $home = new NavigationNodeGeneric();
        $home
            ->setParent($root)
            ->setOrder(0)
            ->setName("Home");

        $lan = new NavigationNodeEmpty();
        $lan
            ->setParent($root)
            ->setOrder(1)
            ->setName("Lan Party");

        $lan_facts = new NavigationNodeContent();
        $lan_facts
            ->setParent($lan)
            ->setName("Facts")
            ->setOrder(0)
            ->setContent($content[0]);

        $lan_facts_net = new NavigationNodeContent();
        $lan_facts_net
            ->setParent($lan_facts)
            ->setName("Netzwerk")
            ->setOrder(0)
            ->setContent($content[4]);

        $lan_facts_catering = new NavigationNodeContent();
        $lan_facts_catering
            ->setParent($lan_facts)
            ->setName("Catering")
            ->setOrder(1)
            ->setContent($content[3]);

        $lan_faq = new NavigationNodeContent();
        $lan_faq
            ->setParent($lan)
            ->setName("FAQ")
            ->setOrder(1)
            ->setContent($content[1]);

        $lan_loc = new NavigationNodeContent();
        $lan_loc
            ->setParent($lan)
            ->setName("Location")
            ->setOrder(2)
            ->setContent($content[2]);

        $admin = new NavigationNodeGeneric();
        $admin
            ->setParent($root)
            ->setName("Admin")
            ->setPath('/admin')
            ->setOrder(2);

        $manager->persist($root);
        $manager->persist($home);
        $manager->persist($lan);
        $manager->persist($lan_facts);
        $manager->persist($lan_facts_net);
        $manager->persist($lan_facts_catering);
        $manager->persist($lan_faq);
        $manager->persist($lan_loc);
        $manager->persist($admin);

        $manager->flush();
    }
}
