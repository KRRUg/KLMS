<?php

namespace App\DataFixtures;

use App\Entity\Tourney;
use App\Entity\TourneyStatus;
use App\Entity\TourneyTeam;
use App\Entity\TourneyGame;
use App\Entity\TourneyTeamMember;
use App\Entity\TourneyRules;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use joshtronic\LoremIpsum;
use Ramsey\Uuid\Uuid;

class TourneyFixture extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $lipsum = new LoremIpsum();

        $tourney0 = (new Tourney())
            ->setName('Chess 1v1')
            ->setDescription($lipsum->words(30))
            ->setHidden(false)
            ->setStatus(TourneyStatus::Running)
            ->setOrder(1)
            ->setToken(20)
            ->setTeamsize(1)
            ->setMode(TourneyRules::SingleElimination)
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
            ->setDescription($lipsum->words(18))
            ->setHidden(false)
            ->setStatus(TourneyStatus::Registration)
            ->setToken(15)
            ->setOrder(3)
            ->setTeamsize(2)
            ->setMode(TourneyRules::SingleElimination)
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
        ;
        $t3 = (new TourneyTeam())->setName('Pro Team 3')
            ->addMember((new TourneyTeamMember())->setGamer(Uuid::fromInteger(3))->setAccepted(true))
            ->addMember((new TourneyTeamMember())->setGamer(Uuid::fromInteger(13))->setAccepted(false))
        ;
        $t4 = (new TourneyTeam())->setName('Not so Pro Team')
            ->addMember((new TourneyTeamMember())->setGamer(Uuid::fromInteger(4))->setAccepted(true))
        ;

        $this->setReference('tourney1-team1', $t1);
        $this->setReference('tourney1-team2', $t2);
        $this->setReference('tourney1-team3', $t3);
        $this->setReference('tourney1-team4', $t4);

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

        $this->setReference('tourney0-game0', $n0);
        $this->setReference('tourney0-game1', $n1);
        $this->setReference('tourney0-game2', $n2);
        $this->setReference('tourney0-game3', $n3);
        $this->setReference('tourney0-game4', $n4);
        $this->setReference('tourney0-game5', $n5);
        $this->setReference('tourney0-game6', $n6);

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
            ->setDescription($lipsum->words(22))
            ->setHidden(false)
            ->setStatus(TourneyStatus::Registration)
            ->setToken(15)
            ->setOrder(2)
            ->setTeamsize(1)
            ->setMode(TourneyRules::RegistrationOnly)
            ->setAuthorId(Uuid::fromInteger(13))
            ->setModifierId(Uuid::fromInteger(13))
        ;

        $tourney2
            ->addTeam(TourneyTeam::createTeamWithUser(UUid::fromInteger(9)));

        $tourney3 = (new Tourney())
            ->setName('Rollerball')
            ->setDescription('')
            ->setHidden(false)
            ->setStatus(TourneyStatus::Registration)
            ->setToken(15)
            ->setOrder(4)
            ->setTeamsize(5)
            ->setMode(TourneyRules::RegistrationOnly)
            ->setAuthorId(Uuid::fromInteger(13))
            ->setModifierId(Uuid::fromInteger(13))
        ;

        // NB: User 1 spent more tokens than allowed (e.g. registered by admins).
        $team = (new TourneyTeam())->setName('Houston');
        $team->addMember(TourneyTeamMember::create(Uuid::fromInteger(1))->setAccepted(true));
        $team->addMember(TourneyTeamMember::create(Uuid::fromInteger(5)));
        $team->addMember(TourneyTeamMember::create(Uuid::fromInteger(6)));
        $team->addMember(TourneyTeamMember::create(Uuid::fromInteger(8))->setAccepted(true));
        $team->addMember(TourneyTeamMember::create(Uuid::fromInteger(9)));
        $tourney3->addTeam($team);

        $this->setReference('tourney-0', $tourney0);
        $this->setReference('tourney-1', $tourney1);
        $this->setReference('tourney-2', $tourney2);
        $this->setReference('tourney-3', $tourney3);
        $manager->persist($tourney0);
        $manager->persist($tourney1);
        $manager->persist($tourney2);
        $manager->persist($tourney3);

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            SettingsFixture::class,
            UserFixtures::class,
        ];
    }
}
