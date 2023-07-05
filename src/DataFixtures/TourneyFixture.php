<?php

namespace App\DataFixtures;

use App\Entity\Tourney;
use App\Entity\TourneyStatus;
use App\Entity\TourneyTeam;
use App\Entity\TourneyGame;
use App\Entity\TourneyTeamMember;
use App\Entity\TourneyType;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Ramsey\Uuid\Uuid;

class TourneyFixture extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $tourney0 = (new Tourney())
            ->setName('Chess 1v1')
            ->setDescription('The classic.')
            ->setHidden(false)
            ->setStatus(TourneyStatus::running)
            ->setOrder(1)
            ->setToken(10)
            ->setTeamsize(1)
            ->setMode(TourneyType::single_elimination)
            ->setShowPoints(true)
            ->setAuthorId(Uuid::fromInteger(12))
            ->setModifierId(Uuid::fromInteger(12))
        ;

        $p1 = TourneyTeam::createTeamWithUser(UUid::fromInteger(1));
        $p2 = TourneyTeam::createTeamWithUser(UUid::fromInteger(2));
        $p3 = TourneyTeam::createTeamWithUser(UUid::fromInteger(3));
        $p4 = TourneyTeam::createTeamWithUser(UUid::fromInteger(4));
        $p5 = TourneyTeam::createTeamWithUser(UUid::fromInteger(5));
        $p6 = TourneyTeam::createTeamWithUser(UUid::fromInteger(6));
        $p7 = TourneyTeam::createTeamWithUser(UUid::fromInteger(7));
        $p8 = TourneyTeam::createTeamWithUser(UUid::fromInteger(8));

        $tourney0
            ->addTeam($p1)
            ->addTeam($p2)
            ->addTeam($p3)
            ->addTeam($p4)
            ->addTeam($p5)
            ->addTeam($p6)
            ->addTeam($p7)
            ->addTeam($p8)
        ;


        $tourney1 = (new Tourney())
            ->setName('Chess 2v2')
            ->setDescription('The team variant.')
            ->setHidden(false)
            ->setStatus(TourneyStatus::registration)
            ->setToken(5)
            ->setOrder(3)
            ->setTeamsize(2)
            ->setMode(TourneyType::single_elimination)
            ->setShowPoints(false)
            ->setAuthorId(Uuid::fromInteger(12))
            ->setModifierId(Uuid::fromInteger(12))
        ;

        $t1 = (new TourneyTeam())->setName('Pro Team 1')
            ->addMember((new TourneyTeamMember())->setGamer(Uuid::fromInteger(1))->setAccepted(true))
            ->addMember((new TourneyTeamMember())->setGamer(Uuid::fromInteger(11))->setAccepted(true))
        ;
        $t2 = (new TourneyTeam())->setName('Pro Team 2')
            ->addMember((new TourneyTeamMember())->setGamer(Uuid::fromInteger(2))->setAccepted(true))
            ->addMember((new TourneyTeamMember())->setGamer(Uuid::fromInteger(12))->setAccepted(false))
        ;
        $t3 = (new TourneyTeam())->setName('Pro Team 3')
            ->addMember((new TourneyTeamMember())->setGamer(Uuid::fromInteger(3))->setAccepted(true))
            ->addMember((new TourneyTeamMember())->setGamer(Uuid::fromInteger(13))->setAccepted(false))
        ;
        $t4 = (new TourneyTeam())->setName('Not so Pro Team')
            ->addMember((new TourneyTeamMember())->setGamer(Uuid::fromInteger(4))->setAccepted(true))
            ->addMember((new TourneyTeamMember())->setGamer(Uuid::fromInteger(14))->setAccepted(true))
        ;

        $tourney1
            ->addTeam($t1)
            ->addTeam($t2)
            ->addTeam($t3)
            ->addTeam($t4)
        ;

        // game tree
        $n0 = (new TourneyGame());
        $n1 = (new TourneyGame())->setParent($n0)->setIsChildA(true);
        $n2 = (new TourneyGame())->setParent($n0)->setIsChildA(false);
        $n3 = (new TourneyGame())->setParent($n1)->setIsChildA(true);
        $n4 = (new TourneyGame())->setParent($n1)->setIsChildA(false);
        $n5 = (new TourneyGame())->setParent($n2)->setIsChildA(true);
        $n6 = (new TourneyGame())->setParent($n2)->setIsChildA(false);

        $n3->setTeamA($p1);
        $n3->setTeamB($p2);
        $n4->setTeamA($p3)->setScoreA(5);
        $n4->setTeamB($p4)->setScoreB(4);
        $n4->getParent()->setTeamB($n4->getTeamA());

        $n5->setTeamA($p5)->setScoreA(2);
        $n5->setTeamB($p6)->setScoreB(3);
        $n5->getParent()->setTeamA($n5->getTeamB());

        $n6->setTeamA($p7);
        $n6->setTeamB($p8);

        $tourney0
            ->addGame($n0)
            ->addGame($n1)
            ->addGame($n2)
            ->addGame($n3)
            ->addGame($n4)
            ->addGame($n5)
            ->addGame($n6)
        ;

        $tourney2 = (new Tourney())
            ->setName('Poker')
            ->setDescription('Some card game.')
            ->setHidden(false)
            ->setStatus(TourneyStatus::registration)
            ->setToken(5)
            ->setOrder(2)
            ->setTeamsize(1)
            ->setMode(TourneyType::registration_only)
            ->setShowPoints(false)
            ->setAuthorId(Uuid::fromInteger(13))
            ->setModifierId(Uuid::fromInteger(13))
        ;

        $this->setReference('tourney-0', $tourney0);
        $this->setReference('tourney-1', $tourney1);
        $this->setReference('tourney-2', $tourney2);
        $manager->persist($tourney0);
        $manager->persist($tourney1);
        $manager->persist($tourney2);

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            SettingsFixture::class,
        ];
    }
}
