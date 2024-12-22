<?php

namespace App\DataFixtures;

use App\Entity\Tourney;
use App\Entity\TourneyGame;
use App\Entity\TourneyStage;
use App\Entity\TourneyTeam;
use App\Entity\TourneyTeamMember;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Ramsey\Uuid\Uuid;

/**
 * Fills up the tourneys to have a completed tournament
 */
class TourneyFixtureGames extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        /** @var Tourney $tourney0 */
        $tourney0 = $this->getReference('tourney-0', Tourney::class);

        $n0 = $this->getReference('tourney0-game0', TourneyGame::class);
        $n1 = $this->getReference('tourney0-game1', TourneyGame::class);
        $n2 = $this->getReference('tourney0-game2', TourneyGame::class);
        $n3 = $this->getReference('tourney0-game3', TourneyGame::class);
        $n6 = $this->getReference('tourney0-game6', TourneyGame::class);

        $n3->setscoreA(4)->setScoreB(3)->getParent()->setTeamA($n3->getTeamA());
        $n6->setscoreA(1)->setScoreB(2)->getParent()->setTeamB($n6->getTeamB());
        $n1->setScoreA(2)->setScoreB(1)->getParent()->setTeamA($n1->getTeamA());
        $n2->setScoreA(6)->setScoreB(3)->getParent()->setTeamB($n2->getTeamA());
        $n0->setScoreA(2)->setScoreB(3);

        $tourney0->setStatus(TourneyStage::Finished);

        $tourney1 = $this->getReference('tourney-1', Tourney::class);
        $t1 = $this->getReference('tourney1-team1', TourneyTeam::class);
        $t2 = $this->getReference('tourney1-team2', TourneyTeam::class);
        $t3 = $this->getReference('tourney1-team3', TourneyTeam::class);
        $t4 = $this->getReference('tourney1-team4', TourneyTeam::class);

        $t2->addMember((new TourneyTeamMember())->setGamer(Uuid::fromInteger(12))->setAccepted(true));
        $t3->getMembers()[1]->setAccepted(true);
        $t4->addMember((new TourneyTeamMember())->setGamer(Uuid::fromInteger(14))->setAccepted(true));
        $t5 = (new TourneyTeam())->setName('Somewhat good team')
            ->addMember((new TourneyTeamMember())->setGamer(Uuid::fromInteger(5))->setAccepted(true))
            ->addMember((new TourneyTeamMember())->setGamer(Uuid::fromInteger(15))->setAccepted(true));
        $tourney1->addTeam($t5);
        $manager->persist($t5);
        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            TourneyFixture::class,
        ];
    }
}
