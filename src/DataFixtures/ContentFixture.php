<?php

namespace App\DataFixtures;

use App\Entity\Content;
use App\Entity\Navigation;
use App\Entity\NavigationNodeContent;
use App\Entity\NavigationNodeEmpty;
use App\Entity\NavigationNodeGeneric;
use App\Entity\NavigationNodeRoot;
use App\Entity\TextBlock;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use joshtronic\LoremIpsum;
use Ramsey\Uuid\Uuid;

class ContentFixture extends Fixture
{
    public function load(ObjectManager $manager)
    {
        $lipsum = new LoremIpsum();

        // Generate Content

        $content[0] = new Content();
        $content[0]->setTitle("Lan is");
        $content[0]->setContent("Lan is wieder einmal.");
        $content[0]->setAuthorId(Uuid::fromInteger(strval(14)));
        $content[0]->setModifierId(Uuid::fromInteger(strval(14)));
        $content[1] = new Content();
        $content[1]->setTitle("FAQ");
        $content[1]->setContent("Wer ist dieser LAN?");
        $content[1]->setAlias("info");
        $content[1]->setAuthorId(Uuid::fromInteger(strval(14)));
        $content[1]->setModifierId(Uuid::fromInteger(strval(14)));
        $content[2] = new Content();
        $content[2]->setTitle("Das Kulturzentrum");
        $content[2]->setDescription("Beschreibung des Kulturzentrum");
        $content[2]->setContent("Wir haben ein paar Sessel gefunden.");
        $content[2]->setAuthorId(Uuid::fromInteger(strval(14)));
        $content[2]->setModifierId(Uuid::fromInteger(strval(14)));
        $content[3] = new Content();
        $content[3]->setTitle("Catering");
        $content[3]->setContent("Es gibt was zu essen");
        $content[3]->setAuthorId(Uuid::fromInteger(strval(14)));
        $content[3]->setModifierId(Uuid::fromInteger(strval(14)));
        $content[4] = new Content();
        $content[4]->setTitle("Netzerk und Internet");
        $content[4]->setContent("Haben wir auch.");
        $content[4]->setAuthorId(Uuid::fromInteger(strval(14)));
        $content[4]->setModifierId(Uuid::fromInteger(strval(14)));

        foreach ($content as $c) {
            $manager->persist($c);
        }


        // Generate Navigation
        $nav = new Navigation();
        $nav->setName('main_menu');

        $root = new NavigationNodeRoot($nav);
        $root->setPos(1, 16);

        $home = new NavigationNodeGeneric($nav);
        $home
            ->setName("Home")
            ->setPos(2, 3);

        $lan = new NavigationNodeEmpty($nav);
        $lan
            ->setName("Lan Party")
            ->setPos(4, 15);

        $lan_facts = new NavigationNodeContent($nav, $content[0]);
        $lan_facts
            ->setName("Facts")
            ->setPos(5, 10);

        $lan_facts_net = new NavigationNodeContent($nav, $content[4]);
        $lan_facts_net
            ->setName("Netzwerk")
            ->setPos(6,7);

        $lan_facts_catering = new NavigationNodeContent($nav, $content[3]);
        $lan_facts_catering
            ->setName("Catering")
            ->setPos(8,9);

        $lan_faq = new NavigationNodeContent($nav, $content[1]);
        $lan_faq
            ->setName("FAQ")
            ->setPos(11,12);

        $lan_loc = new NavigationNodeContent($nav, $content[2]);
        $lan_loc
            ->setName("Location")
            ->setPos(13, 14);

        $manager->persist($nav);
        $manager->persist($root);
        $manager->persist($home);
        $manager->persist($lan);
        $manager->persist($lan_facts);
        $manager->persist($lan_facts_net);
        $manager->persist($lan_facts_catering);
        $manager->persist($lan_faq);
        $manager->persist($lan_loc);

        $links = new Navigation();
        $links->setName("footer");
        $links->addNode((new NavigationNodeRoot($links))->setName('Footer')->setPos(1,8));
        $links->addNode((new NavigationNodeGeneric($links))->setName('AGB')->setPath('/')->setPos(2,3));
        $links->addNode((new NavigationNodeGeneric($links))->setName('Impressum')->setPath('/')->setPos(4,5));
        $links->addNode((new NavigationNodeGeneric($links))->setName('Datenschutz')->setPath('/')->setPos(6,7));

        $manager->persist($links);

        $manager->flush();
        $manager->refresh($nav);
        $manager->refresh($links);

        // Generate Textblocks
        $tb_about = new TextBlock("ABOUT_US");
        $tb_about->setText($lipsum->words(20));

        $tb_agb = new TextBlock("AGB");
        $tb_agb->setText("<h2>{$lipsum->words()}</h2><p>{$lipsum->paragraphs(2)}</p><h2>{$lipsum->words(2)}</h2><p>{$lipsum->paragraphs(3)}}</p>");

        $manager->persist($tb_about);
        $manager->persist($tb_agb);

        $manager->flush();
    }
}
