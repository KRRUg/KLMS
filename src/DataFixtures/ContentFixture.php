<?php

namespace App\DataFixtures;

use App\Entity\Content;
use App\Entity\Navigation;
use App\Entity\NavigationNodeContent;
use App\Entity\NavigationNodeEmpty;
use App\Entity\NavigationNodeGeneric;
use App\Entity\NavigationNodeRoot;
use App\Entity\NavigationNodeTeamsite;
use App\Entity\Teamsite;
use App\Entity\TeamsiteCategory;
use App\Entity\TeamsiteEntry;
use App\Entity\TextBlock;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use joshtronic\LoremIpsum;
use Ramsey\Uuid\Uuid;

class ContentFixture extends Fixture
{
    private function createContent(ObjectManager $manager): array
    {
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

        return $content;
    }

    private function createTeamsite(ObjectManager $manager): array
    {
        $ts = (new Teamsite())
            ->setTitle("KLMS-Team")
            ->setDescription("Das Team hinter dem KLMS.")
            ->setAuthorId(Uuid::fromInteger(18))
            ->setModifierId(Uuid::fromInteger(18))
        ;

        $cat1 = (new TeamsiteCategory())
            ->setTitle("Backend")
            ->setDescription("Die Backend Developer")
            ->setOrd(1)
            ->addEntry((new TeamsiteEntry())
                ->setTitle('Chief Developer')
                ->setUserUuid(Uuid::fromInteger(18))
                ->setDescription('<i>Hacky</i> Hacker')
                ->setOrd(1)
            )
            ->addEntry((new TeamsiteEntry())
                ->setTitle('Senior Developer')
                ->setUserUuid(Uuid::fromInteger(7))
                ->setOrd(3)
            )
            ->addEntry((new TeamsiteEntry())
                ->setTitle('Senior Developer')
                ->setUserUuid(Uuid::fromInteger(18))
                ->setOrd(2)
            );

        $cat2 = (new TeamsiteCategory())
            ->setTitle("Frontend")
            ->setOrd(2)
            ->addEntry((new TeamsiteEntry())
                ->setUserUuid(Uuid::fromInteger(13))
                ->setTitle("JS Developer")
                ->setDescription("I ❤ JS")
                ->setOrd(1)
            );

        $cat3 = (new TeamsiteCategory())
            ->setTitle('Q&A Team')
            ->setOrd(3);

        $ts->addCategory($cat1);
        $ts->addCategory($cat2);
        $ts->addCategory($cat3);

        $manager->persist($ts);

        return [$ts];
    }

    private function createTextblock(ObjectManager $manager): array
    {
        $lipsum = new LoremIpsum();

        $tb_reg = new TextBlock("organisation_name");
        $tb_reg->setText('KLMS Team');

        $tb_subject = new TextBlock("email.register.subject");
        $tb_subject->setText('Registrierung');

        $tb_about = new TextBlock("about_us");
        $tb_about->setText($lipsum->words(20));

        $tb_agb = new TextBlock("agb");
        $tb_agb->setText("<h2>{$lipsum->words()}</h2><p>{$lipsum->paragraphs(2)}</p><h2>{$lipsum->words(2)}</h2><p>{$lipsum->paragraphs(3)}}</p>");

        $manager->persist($tb_reg);
        $manager->persist($tb_about);
        $manager->persist($tb_agb);
        $manager->persist($tb_subject);

        return [$tb_about, $tb_agb, $tb_reg, $tb_subject];
    }

    public function load(ObjectManager $manager)
    {
        $tb = $this->createTextblock($manager);
        $ts = $this->createTeamsite($manager);
        $content = $this->createContent($manager);

        // Generate Navigation
        $nav = new Navigation();
        $nav->setName('main_menu');
        $nav->setMaxDepth(2);
        $nav->addNode((new NavigationNodeRoot())->setPos(1,18));
        $nav->addNode((new NavigationNodeGeneric())->setName("Home")->setPos(2,3));
        $nav->addNode((new NavigationNodeEmpty())->setName("Lan Party")->setPos(4, 15));
        $nav->addNode((new NavigationNodeContent($content[0]))->setName("Facts")->setPos(5, 6));
        $nav->addNode((new NavigationNodeContent($content[4]))->setName("Netzwerk")->setPos(7,8));
        $nav->addNode((new NavigationNodeContent($content[3]))->setName("Catering")->setPos(9,10));
        $nav->addNode((new NavigationNodeContent($content[1]))->setName("FAQ")->setPos(11,12));
        $nav->addNode((new NavigationNodeContent($content[2]))->setName("Location")->setPos(13, 14));
        $nav->addNode((new NavigationNodeTeamsite($ts[0]))->setName("Team")->setPos(16,17));
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

        $manager->flush();
    }
}
