<?php

namespace App\DataFixtures;

use App\Entity\Content;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Ramsey\Uuid\Uuid;

class ContentFixture extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $content = [];
        // Generate Content
        $content[0] = (new Content())
            ->setTitle('Lan is')
            ->setContent('Lan is wieder einmal.')
            ->setAuthorId(Uuid::fromInteger(strval(14)))
            ->setModifierId(Uuid::fromInteger(strval(14)));
        $content[1] = (new Content())
            ->setTitle('FAQ')
            ->setContent('Wer ist dieser LAN?')
            ->setAlias('info')
            ->setAuthorId(Uuid::fromInteger(strval(14)))
            ->setModifierId(Uuid::fromInteger(strval(14)));
        $content[2] = (new Content())
            ->setTitle('Das Kulturzentrum')
            ->setDescription('Beschreibung des Kulturzentrum')
            ->setContent('Wir haben ein paar Sessel gefunden.')
            ->setAuthorId(Uuid::fromInteger(strval(14)))
            ->setModifierId(Uuid::fromInteger(strval(14)));
        $content[3] = (new Content())
            ->setTitle('Catering')
            ->setContent('Es gibt was zu essen')
            ->setAuthorId(Uuid::fromInteger(strval(14)))
            ->setModifierId(Uuid::fromInteger(strval(14)));
        $content[4] = (new Content())
            ->setTitle('Netzwerk und Internet')
            ->setContent('Haben wir auch.')
            ->setAuthorId(Uuid::fromInteger(strval(14)))
            ->setModifierId(Uuid::fromInteger(strval(14)));
        $content[5] = (new Content())
            ->setTitle('Einzahlung')
            ->setContent('Geld überweisen!')
            ->setAlias('payment')
            ->setAuthorId(Uuid::fromInteger(strval(14)))
            ->setModifierId(Uuid::fromInteger(strval(14)));
        $content[6] = (new Content())
            ->setTitle('AGB')
            ->setContent('Legal foo.')
            ->setAlias('agb')
            ->setAuthorId(Uuid::fromInteger(strval(14)))
            ->setModifierId(Uuid::fromInteger(strval(14)));

        for ($i = 0; $i < count($content); $i++) {
            $c = $content[$i];
            $manager->persist($c);
            $this->setReference('content-'.$i, $c);
        }

        $manager->flush();
    }
}
