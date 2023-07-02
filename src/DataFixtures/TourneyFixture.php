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
            ->setOrder(1)
            ->setTeamsize(1)
            ->setMode(Tourney::MODE_SINGLE_ELIMINATION)
            ->setResultType(Tourney::RESULT_TYPE_POINTS)
            ->setAuthorId(Uuid::fromInteger(12))
            ->setModifierId(Uuid::fromInteger(12))
        ;

        $p1 = (new TourneyEntrySinglePlayer())->setGamer(UUid::fromInteger(1));
        $p2 = (new TourneyEntrySinglePlayer())->setGamer(UUid::fromInteger(2));
        $p3 = (new TourneyEntrySinglePlayer())->setGamer(UUid::fromInteger(3));
        $p4 = (new TourneyEntrySinglePlayer())->setGamer(UUid::fromInteger(4));
        $p5 = (new TourneyEntrySinglePlayer())->setGamer(UUid::fromInteger(5));
        $p6 = (new TourneyEntrySinglePlayer())->setGamer(UUid::fromInteger(6));
        $p7 = (new TourneyEntrySinglePlayer())->setGamer(UUid::fromInteger(7));
        $p8 = (new TourneyEntrySinglePlayer())->setGamer(UUid::fromInteger(8));

        $tourney0
            ->addEntry($p1)
            ->addEntry($p2)
            ->addEntry($p3)
            ->addEntry($p4)
            ->addEntry($p5)
            ->addEntry($p6)
            ->addEntry($p7)
            ->addEntry($p8)
        ;


        $tourney1 = (new Tourney())
            ->setName('Chess 2v2')
            ->setDescription('The team variant.')
            ->setHidden(false)
            ->setOrder(2)
            ->setTeamsize(2)
            ->setMode(Tourney::MODE_SINGLE_ELIMINATION)
            ->setResultType(Tourney::RESULT_TYPE_WON_LOST)
            ->setAuthorId(Uuid::fromInteger(12))
            ->setModifierId(Uuid::fromInteger(12))
        ;

        $t1 = (new TourneyEntryTeam())
            ->addMember((new TourneyTeamMember())->setGamer(Uuid::fromInteger(1))->setAccepted(true))
            ->addMember((new TourneyTeamMember())->setGamer(Uuid::fromInteger(11))->setAccepted(true))
        ;
        $t2 = (new TourneyEntryTeam())
            ->addMember((new TourneyTeamMember())->setGamer(Uuid::fromInteger(2))->setAccepted(true))
            ->addMember((new TourneyTeamMember())->setGamer(Uuid::fromInteger(12))->setAccepted(false))
        ;
        $t3 = (new TourneyEntryTeam())
            ->addMember((new TourneyTeamMember())->setGamer(Uuid::fromInteger(3))->setAccepted(true))
            ->addMember((new TourneyTeamMember())->setGamer(Uuid::fromInteger(13))->setAccepted(false))
        ;
        $t4 = (new TourneyEntryTeam())
            ->addMember((new TourneyTeamMember())->setGamer(Uuid::fromInteger(4))->setAccepted(true))
            ->addMember((new TourneyTeamMember())->setGamer(Uuid::fromInteger(14))->setAccepted(true))
        ;

        $tourney1
            ->addEntry($t1)
            ->addEntry($t2)
            ->addEntry($t3)
            ->addEntry($t4)
        ;

        // game tree
        $n0 = (new TourneyGame());
        $n1 = (new TourneyGame())->setParent($n0)->setIsChildA(true);
        $n2 = (new TourneyGame())->setParent($n0)->setIsChildA(false);
        $n3 = (new TourneyGame())->setParent($n1)->setIsChildA(true);
        $n4 = (new TourneyGame())->setParent($n1)->setIsChildA(false);
        $n5 = (new TourneyGame())->setParent($n2)->setIsChildA(true);
        $n6 = (new TourneyGame())->setParent($n2)->setIsChildA(false);

        $n3->setEntryA($p1);
        $n3->setEntryB($p2);
        $n4->setEntryA($p3)->setScoreA(5);
        $n4->setEntryB($p4)->setScoreB(4);
        $n4->getParent()->setEntryB($n4->getEntryA());

        $n5->setEntryA($p5)->setScoreA(2);
        $n5->setEntryB($p6)->setScoreB(3);
        $n5->getParent()->setEntryA($n5->getEntryB());

        $n6->setEntryA($p7);
        $n6->setEntryB($p8);

        $tourney0
            ->addGame($n0)
            ->addGame($n1)
            ->addGame($n2)
            ->addGame($n3)
            ->addGame($n4)
            ->addGame($n5)
            ->addGame($n6)
        ;

        $this->setReference('tourney-0', $tourney0);
        $this->setReference('tourney-1', $tourney1);
        $manager->persist($tourney0);
        $manager->persist($tourney1);

        $manager->flush();
    }
}
