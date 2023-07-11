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
use joshtronic\LoremIpsum;
use Ramsey\Uuid\Uuid;

/**
 * Fills up the tourneys to have a completed tournament
 */
class TourneyFixtureGames extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        /** @var Tourney $tourney0 */
        $tourney0 = $this->getReference('tourney-0');

        $n0 = $this->getReference('tourney0-game0');
        $n1 = $this->getReference('tourney0-game1');
        $n2 = $this->getReference('tourney0-game2');
        $n3 = $this->getReference('tourney0-game3');
        $n6 = $this->getReference('tourney0-game6');

        $n3->setscoreA(4)->setScoreB(3)->getParent()->setTeamA($n3->getTeamA());
        $n6->setscoreA(1)->setScoreB(2)->getParent()->setTeamB($n6->getTeamB());
        $n1->setScoreA(2)->setScoreB(1)->getParent()->setTeamA($n1->getTeamA());
        $n2->setScoreA(6)->setScoreB(3)->getParent()->setTeamB($n2->getTeamA());
        $n0->setScoreA(2)->setScoreB(3);

        $tourney0->setStatus(TourneyStatus::Finished);
        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            TourneyFixture::class,
        ];
    }
}
