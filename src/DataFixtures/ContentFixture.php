<?php

namespace App\DataFixtures;

use App\Entity\Content;
use App\Entity\Navigation;
use App\Entity\NavigationNodeContent;
use App\Entity\NavigationNodeEmpty;
use App\Entity\NavigationNodeGeneric;
use App\Entity\NavigationNodeRoot;
use App\Entity\NavigationNodeTeamsite;
use App\Entity\Setting;
use App\Entity\Teamsite;
use App\Entity\TeamsiteCategory;
use App\Entity\TeamsiteEntry;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use joshtronic\LoremIpsum;
use Ramsey\Uuid\Uuid;

class ContentFixture extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $content = [];
        // Generate Content
        $content[0] = new Content();
        $content[0]->setTitle('Lan is');
        $content[0]->setContent('Lan is wieder einmal.');
        $content[0]->setAuthorId(Uuid::fromInteger(strval(14)));
        $content[0]->setModifierId(Uuid::fromInteger(strval(14)));
        $content[1] = new Content();
        $content[1]->setTitle('FAQ');
        $content[1]->setContent('Wer ist dieser LAN?');
        $content[1]->setAlias('info');
        $content[1]->setAuthorId(Uuid::fromInteger(strval(14)));
        $content[1]->setModifierId(Uuid::fromInteger(strval(14)));
        $content[2] = new Content();
        $content[2]->setTitle('Das Kulturzentrum');
        $content[2]->setDescription('Beschreibung des Kulturzentrum');
        $content[2]->setContent('Wir haben ein paar Sessel gefunden.');
        $content[2]->setAuthorId(Uuid::fromInteger(strval(14)));
        $content[2]->setModifierId(Uuid::fromInteger(strval(14)));
        $content[3] = new Content();
        $content[3]->setTitle('Catering');
        $content[3]->setContent('Es gibt was zu essen');
        $content[3]->setAuthorId(Uuid::fromInteger(strval(14)));
        $content[3]->setModifierId(Uuid::fromInteger(strval(14)));
        $content[4] = new Content();
        $content[4]->setTitle('Netzwerk und Internet');
        $content[4]->setContent('Haben wir auch.');
        $content[4]->setAuthorId(Uuid::fromInteger(strval(14)));
        $content[4]->setModifierId(Uuid::fromInteger(strval(14)));
        $content[5] = new Content();
        $content[5]->setTitle('Einzahlung');
        $content[5]->setContent('Geld überweisen!');
        $content[5]->setAlias('payment');
        $content[5]->setAuthorId(Uuid::fromInteger(strval(14)));
        $content[5]->setModifierId(Uuid::fromInteger(strval(14)));

        for ($i = 0; $i < count($content); $i++) {
            $c = $content[$i];
            $manager->persist($c);
            $this->setReference('content-'.$i, $c);
        }

        $manager->flush();
    }
}
