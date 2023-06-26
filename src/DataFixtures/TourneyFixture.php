<?php

namespace App\DataFixtures;

use App\Entity\Tourney;
use App\Entity\TourneyEntrySinglePlayer;
use App\Entity\TourneyEntryTeam;
use App\Entity\TourneyGame;
use App\Entity\TourneyTeamMember;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Ramsey\Uuid\Uuid;

class TourneyFixture extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $tourney0 = (new Tourney())
            ->setName('Chess 1v1')
            ->setDescription('The classic.')
            ->setHidden(false)
            ->setTeamsize(1)
            ->setMode(Tourney::MODE_SINGLE_ELIMINATION)
            ->setResultType(Tourney::RESULT_TYPE_WON_LOST)
            ->setAuthorId(Uuid::fromInteger(12))
            ->setModifierId(Uuid::fromInteger(12))
        ;

        $p1 = (new TourneyEntrySinglePlayer())->setTourney($tourney0)->setPlayer(UUid::fromInteger(1));
        $p2 = (new TourneyEntrySinglePlayer())->setTourney($tourney0)->setPlayer(UUid::fromInteger(2));
        $p3 = (new TourneyEntrySinglePlayer())->setTourney($tourney0)->setPlayer(UUid::fromInteger(3));
        $p4 = (new TourneyEntrySinglePlayer())->setTourney($tourney0)->setPlayer(UUid::fromInteger(4));

        $tourney0
            ->addEntry($p1)
            ->addEntry($p2)
            ->addEntry($p3)
            ->addEntry($p4)
        ;


        $tourney1 = (new Tourney())
            ->setName('Chess 2v2')
            ->setDescription('The team variant.')
            ->setHidden(false)
            ->setTeamsize(2)
            ->setMode(Tourney::MODE_SINGLE_ELIMINATION)
            ->setResultType(Tourney::RESULT_TYPE_WON_LOST)
            ->setAuthorId(Uuid::fromInteger(12))
            ->setModifierId(Uuid::fromInteger(12))
        ;

        $t1 = (new TourneyEntryTeam())->setTourney($tourney1)
            ->addMember((new TourneyTeamMember())->setGamer(Uuid::fromInteger(1))->setAccepted(true))
            ->addMember((new TourneyTeamMember())->setGamer(Uuid::fromInteger(11))->setAccepted(true))
        ;
        $t2 = (new TourneyEntryTeam())->setTourney($tourney1)
            ->addMember((new TourneyTeamMember())->setGamer(Uuid::fromInteger(2))->setAccepted(true))
            ->addMember((new TourneyTeamMember())->setGamer(Uuid::fromInteger(12))->setAccepted(false))
        ;
        $t3 = (new TourneyEntryTeam())->setTourney($tourney1)
            ->addMember((new TourneyTeamMember())->setGamer(Uuid::fromInteger(3))->setAccepted(true))
            ->addMember((new TourneyTeamMember())->setGamer(Uuid::fromInteger(13))->setAccepted(false))
        ;
        $t4 = (new TourneyEntryTeam())->setTourney($tourney1)
            ->addMember((new TourneyTeamMember())->setGamer(Uuid::fromInteger(4))->setAccepted(true))
            ->addMember((new TourneyTeamMember())->setGamer(Uuid::fromInteger(14))->setAccepted(true))
        ;

        $tourney1
            ->addEntry($t1)
            ->addEntry($t2)
            ->addEntry($t3)
            ->addEntry($t4)
        ;

        $this->setReference('tourney-0', $tourney0);
        $this->setReference('tourney-1', $tourney1);
        $manager->persist($tourney0);
        $manager->persist($tourney1);

        $manager->flush();
    }
}
