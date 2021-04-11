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
use Doctrine\Persistence\ObjectManager;
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
        $nav->setMaxDepth(2);
        $nav->addNode((new NavigationNodeRoot())->setPos(1,16));
        $nav->addNode((new NavigationNodeGeneric())->setName("Home")->setPos(2,3));
        $nav->addNode((new NavigationNodeEmpty())->setName("Lan Party")->setPos(4, 15));
        $nav->addNode((new NavigationNodeContent($content[0]))->setName("Facts")->setPos(5, 6));
        $nav->addNode((new NavigationNodeContent($content[4]))->setName("Netzwerk")->setPos(7,8));
        $nav->addNode((new NavigationNodeContent($content[3]))->setName("Catering")->setPos(9,10));
        $nav->addNode((new NavigationNodeContent($content[1]))->setName("FAQ")->setPos(11,12));
        $nav->addNode((new NavigationNodeContent($content[2]))->setName("Location")->setPos(13, 14));
        $manager->persist($nav);

        $footer = new Navigation();
        $footer->setName("footer");
        $footer->setMaxDepth(1);
        $footer->addNode((new NavigationNodeRoot())->setName('Footer')->setPos(1,8));
        $footer->addNode((new NavigationNodeGeneric())->setName('AGB')->setPath('/')->setPos(2,3));
        $footer->addNode((new NavigationNodeGeneric())->setName('Impressum')->setPath('/')->setPos(4,5));
        $footer->addNode((new NavigationNodeGeneric())->setName('Datenschutz')->setPath('/')->setPos(6,7));

        $manager->persist($footer);

        $manager->flush();
        $manager->refresh($nav);
        $manager->refresh($footer);

        // Generate Textblocks
        $tb_reg = new TextBlock("organisation_name");
        $tb_reg->setText('KLMS Team');

        $tb_subject = new TextBlock("register.subject");
        $tb_subject->setText('Registrierung');

        $tb_about = new TextBlock("about_us");
        $tb_about->setText($lipsum->words(20));

        $tb_agb = new TextBlock("agb");
        $tb_agb->setText("<h2>{$lipsum->words()}</h2><p>{$lipsum->paragraphs(2)}</p><h2>{$lipsum->words(2)}</h2><p>{$lipsum->paragraphs(3)}}</p>");

        $manager->persist($tb_reg);
        $manager->persist($tb_about);
        $manager->persist($tb_agb);
        $manager->persist($tb_subject);

        $manager->flush();
    }
}
