<?php

namespace App\DataFixtures;

use App\Entity\Tourney;
use App\Entity\TourneyEntrySinglePlayer;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Ramsey\Uuid\Uuid;

class TourneyFixture extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $t = (new Tourney())
            ->setName('Chess 1v1')
            ->setDescription('The classic.')
            ->setHidden(false)
            ->setMode(Tourney::MODE_SINGLE_ELIMINATION)
            ->setResultType(Tourney::RESULT_TYPE_WON_LOST)
            ->setAuthorId(Uuid::fromInteger(12))
            ->setModifierId(Uuid::fromInteger(12))
        ;

        $p1 = (new TourneyEntrySinglePlayer())->setTourney($t)->setPlayer(UUid::fromInteger(2));
        $p2 = (new TourneyEntrySinglePlayer())->setTourney($t)->setPlayer(UUid::fromInteger(3));
        $p3 = (new TourneyEntrySinglePlayer())->setTourney($t)->setPlayer(UUid::fromInteger(4));
        $p4 = (new TourneyEntrySinglePlayer())->setTourney($t)->setPlayer(UUid::fromInteger(5));

        $this->setReference('tourney-0', $t);
        $manager->persist($t);
    }
}
