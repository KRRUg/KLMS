<?php

namespace App\Tests\Integration\Service;

use App\DataFixtures\TourneyFixture;
use App\DataFixtures\TourneyFixtureGames;
use App\DataFixtures\UserFixtures;
use App\Entity\Tourney;
use App\Entity\TourneyGame;
use App\Entity\TourneyRules;
use App\Entity\TourneyStage;
use App\Entity\TourneyTeam;
use App\Entity\User;
use App\Exception\ServiceException;
use App\Idm\IdmManager;
use App\Service\TourneyService;
use App\Tests\Integration\DatabaseTestCase;
use LogicException;
use Ramsey\Uuid\Nonstandard\Uuid;

class TourneyServiceIntegrationTest extends DatabaseTestCase
{
    private function getUser(int $id): ?User
    {
        $manager = self::getContainer()->get(IdmManager::class);
        $userRepo = $manager->getRepository(User::class);
        return $userRepo->findOneById(Uuid::fromInteger($id));
    }

    public function testGetTourneys()
    {
        $this->databaseTool->loadFixtures([TourneyFixture::class, UserFixtures::class]);
        $service = self::getContainer()->get(TourneyService::class);

        /** @var Tourney[] $tourneys */
        $tourneys = $service->getVisibleTourneys();
        $this->assertCount(4, $tourneys);
        $this->assertEquals('Chess 1v1', $tourneys[0]->getName());
        $this->assertEquals('Poker', $tourneys[1]->getName());
        $this->assertEquals('Chess 2v2', $tourneys[2]->getName());
    }

    public function testUserRegisterSinglePlayer()
    {
        $this->databaseTool->loadFixtures([TourneyFixture::class, UserFixtures::class]);
        $service = self::getContainer()->get(TourneyService::class);
        $user7 = $this->getUser(7);

        $tourney = $service->getVisibleTourneys()[1];
        $this->assertEquals(TourneyStage::Registration, $tourney->getStatus());
        $this->assertTrue($tourney->isSinglePlayer());

        $this->assertTrue($service->userCanRegisterForTourney($tourney, $user7));
        $service->userRegister($tourney, $user7, null);
        $this->assertContains($tourney, $service->getRegisteredTourneys($user7));

        $tm = $service->getTeamMemberByTourneyAndUser($tourney, $user7);
        $this->assertNotEmpty($tm);
        $this->assertNotEmpty($tm->getTeam());
        $this->assertNull($tm->getTeam()->getName());
        $this->assertCount(1, $tm->getTeam()->getMembers());
    }

    public function testUserRegisterNewTeam()
    {
        $this->databaseTool->loadFixtures([TourneyFixture::class, UserFixtures::class]);
        $service = self::getContainer()->get(TourneyService::class);
        $user7 = $this->getUser(7);

        $tourney = $service->getVisibleTourneys()[2];
        $this->assertEquals(TourneyStage::Registration, $tourney->getStatus());
        $service->userRegister($tourney, $user7, 'Pro Team');
        $this->assertContains($tourney, $service->getRegisteredTourneys($user7));
        $tm = $service->getTeamMemberByTourneyAndUser($tourney, $user7);
        $this->assertNotEmpty($tm);
        $this->assertTrue($tm->isAccepted());
        $this->assertEquals('Pro Team', $tm->getTeam()->getName());
        $this->assertCount(1, $tm->getTeam()->getMembers());
    }

    public function testUserRegistrationJoinTeam()
    {
        $this->databaseTool->loadFixtures([TourneyFixture::class, UserFixtures::class]);
        $service = self::getContainer()->get(TourneyService::class);
        $user7 = $this->getUser(7);
        $user8 = $this->getUser(14);

        $tourney = $service->getVisibleTourneys()[2];
        $this->assertEquals(TourneyStage::Registration, $tourney->getStatus());
        $this->assertEquals(2, $tourney->getTeamsize());

        $service->userRegister($tourney, $user7, 'New Team');
        $tm = $service->getTeamMemberByTourneyAndUser($tourney, $user7);
        $this->assertNotEmpty($tm);
        $team = $tm->getTeam();

        $this->assertTrue($service->userCanRegisterForTourney($tourney, $user8));
        $service->userRegister($tourney, $user8, $team);
        $tm = $service->getTeamMemberByTourneyAndUser($tourney, $user8);
        $this->assertFalse($tm->isAccepted());

        $this->assertContains($tourney, $service->getRegisteredTourneys($user7));
        $this->assertContains($tourney, $service->getRegisteredTourneys($user8));
    }

    public function testUserRegisterInsufficientToken()
    {
        $this->databaseTool->loadFixtures([TourneyFixture::class, UserFixtures::class]);
        $service = self::getContainer()->get(TourneyService::class);
        $user7 = $this->getUser(7);

        $tourneys = $service->getVisibleTourneys();
        $service->userRegister($tourneys[1], $user7, null);
        $this->expectException(ServiceException::class);
        $this->expectExceptionCode(ServiceException::CAUSE_FORBIDDEN);
        $service->userRegister($tourneys[2], $user7, 'New Team');
    }

    public function testUserRegistrationConfirmMemberAccept()
    {
        $this->databaseTool->loadFixtures([TourneyFixture::class, UserFixtures::class]);
        $service = self::getContainer()->get(TourneyService::class);
        $user2 = $this->getUser(2);
        $user8 = $this->getUser(14);

        $tourney = $service->getVisibleTourneys()[2];

        $team = $service->getTeamMemberByTourneyAndUser($tourney, $user2)->getTeam();
        $service->userRegister($tourney, $user8, $team);

        $service->userConfirm($tourney, $user8, $user2, true);
        $this->assertContains($tourney, $service->getRegisteredTourneys($user8));
        $tm = $service->getTeamMemberByTourneyAndUser($tourney, $user8);
        $this->assertTrue($tm->isAccepted());
    }

    public function testUserRegistrationConfirmMemberDecline()
    {
        $this->databaseTool->loadFixtures([TourneyFixture::class, UserFixtures::class]);
        $service = self::getContainer()->get(TourneyService::class);
        $user2 = $this->getUser(2);
        $user8 = $this->getUser(14);

        $tourney = $service->getVisibleTourneys()[2];

        $team = $service->getTeamMemberByTourneyAndUser($tourney, $user2)->getTeam();
        $service->userRegister($tourney, $user8, $team);

        $service->userConfirm($tourney, $user8, $user2, false);
        $this->assertNotContains($tourney, $service->getRegisteredTourneys($user8));
        $this->assertNull($service->getTeamMemberByTourneyAndUser($tourney, $user8));
    }

    public function testUserRegisterNewTeamInvalidTeam()
    {
        $this->databaseTool->loadFixtures([TourneyFixture::class, UserFixtures::class]);
        $service = self::getContainer()->get(TourneyService::class);
        $user7 = $this->getUser(7);

        $tourney = $service->getVisibleTourneys()[2];
        $this->assertEquals(TourneyStage::Registration, $tourney->getStatus());
        $team = TourneyTeam::createTeamWithUser($user7->getUuid());
        $this->expectException(LogicException::class);
        $service->userRegister($tourney, $user7, $team);
    }

    public function testUserRegisterStaredTourney()
    {
        $this->databaseTool->loadFixtures([TourneyFixture::class, UserFixtures::class]);
        $service = self::getContainer()->get(TourneyService::class);
        $user7 = $this->getUser(7);

        $tourney = $service->getVisibleTourneys()[0];
        $this->assertEquals(TourneyStage::Running, $tourney->getStatus());

        $this->expectException(ServiceException::class);
        $service->userRegister($tourney, $user7, null);
    }

    public function testRegisterAlreadyRegistered()
    {
        $this->databaseTool->loadFixtures([TourneyFixture::class, UserFixtures::class]);
        $service = self::getContainer()->get(TourneyService::class);
        $user7 = $this->getUser(7);

        $tourney = $service->getVisibleTourneys()[1];
        $this->assertEquals(TourneyStage::Registration, $tourney->getStatus());

        $service->userRegister($tourney, $user7, null);
        $this->expectException(ServiceException::class);
        $service->userRegister($tourney, $user7, null);
    }

    public function testRegisterIncorrectTeamType()
    {
        $this->databaseTool->loadFixtures([TourneyFixture::class, UserFixtures::class]);
        $service = self::getContainer()->get(TourneyService::class);
        $user7 = $this->getUser(7);

        $tourney = $service->getVisibleTourneys()[2];
        $this->assertEquals(TourneyStage::Registration, $tourney->getStatus());

        $this->assertTrue($service->userCanRegisterForTourney($tourney, $user7));
        $this->expectException(ServiceException::class);
        $service->userRegister($tourney, $user7, null);
    }

    public function testUserUnregister()
    {
        $this->databaseTool->loadFixtures([TourneyFixture::class, UserFixtures::class]);
        $service = self::getContainer()->get(TourneyService::class);

        $user2 = $this->getUser(2);
        $tourneys = $service->getRegisteredTourneys($user2);
        $this->assertCount(2, $tourneys);
        $tourney = $tourneys[1];
        $this->assertEquals(TourneyStage::Registration, $tourney->getStatus());
        $team = $service->getTeamMemberByTourneyAndUser($tourney, $user2)->getTeam();

        $service->userUnregister($tourney, $user2);
        $this->assertNotContains($tourney, $service->getRegisteredTourneys($user2));
        $this->assertNull($team->getId());
        $this->assertNull($service->getTeamMemberByTourneyAndUser($tourney, $user2));
    }

    public function testUserUnregisterNotRegistered()
    {
        $this->databaseTool->loadFixtures([TourneyFixture::class, UserFixtures::class]);
        $service = self::getContainer()->get(TourneyService::class);

        $user18 = $this->getUser(18);
        $tourney = $service->getVisibleTourneys()[1];
        $this->assertEquals(TourneyStage::Registration, $tourney->getStatus());
        $this->expectException(ServiceException::class);
        $service->userUnregister($tourney, $user18);
    }

    public function testRegisterNotParticipatingUser()
    {
        $this->databaseTool->loadFixtures([TourneyFixture::class, UserFixtures::class]);
        $service = self::getContainer()->get(TourneyService::class);

        $user0 = $this->getUser(0);
        $tourney = $service->getVisibleTourneys()[1];
        $this->assertEquals(TourneyStage::Registration, $tourney->getStatus());
        $this->expectException(ServiceException::class);
        $service->userRegister($tourney, $user0, null);
    }

    private function provideLogResultInvalidUser(): array
    {
        return [
            [$this->getUser(7), null],
            [$this->getUser(8), '/loser/i'], // user must enter the result
            [$this->getUser(2), '//'], // user is not part of the game
        ];
    }

    /**
     * @dataProvider provideLogResultInvalidUser
     */
    public function testLogResultInvalidUser(User $user, ?string $exception)
    {
        $this->databaseTool->loadFixtures([TourneyFixture::class, UserFixtures::class]);
        $service = self::getContainer()->get(TourneyService::class);
        /** @var Tourney $tourney */
        $tourney = $service->getVisibleTourneys()[0];
        $this->assertTrue($tourney->getStatus() == TourneyStage::Running);

        $game = $service->getGameByTourneyAndUser($tourney, $user);
        $this->assertNotEmpty($game);

        if (!is_null($exception)) {
            $this->expectException(ServiceException::class);
            $this->expectExceptionMessageMatches($exception);
        }
        $service->logResultUser($game, $user, 1, 2);
    }

    public function testRegisterWinAndAdvance()
    {
        $this->databaseTool->loadFixtures([TourneyFixture::class, UserFixtures::class]);
        $service = self::getContainer()->get(TourneyService::class);
        /** @var Tourney $tourney */
        $tourney = $service->getVisibleTourneys()[0];
        $user8 = $this->getUser(8);

        $game = $service->getGameByTourneyAndUser($tourney, $user8);
        $this->assertNotEmpty($game);

        $service->logResult($game, 1, 2);

        $nextGame = $service->getGameByTourneyAndUser($tourney, $user8);
        $this->assertNotEquals($game, $nextGame);
        $this->assertEquals($game->getParent(), $nextGame);
        $this->assertEquals($game->getTeamB(), $nextGame->getTeamB());
    }

    public function testLogResultNotRunningTourney()
    {
        $this->databaseTool->loadFixtures([TourneyFixture::class, UserFixtures::class]);
        $service = self::getContainer()->get(TourneyService::class);
        $tourney = $service->getVisibleTourneys()[0];
        $service->advance($tourney);

        $user7 = $this->getUser(7);

        $game = $service->getGameByTourneyAndUser($tourney, $user7);
        $this->assertNotEmpty($game);

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessageMatches('/not running/i');
        $service->logResultUser($game, $user7, 1, 2);
    }

    public function testTourneyPodium()
    {
        $this->databaseTool->loadFixtures([TourneyFixtureGames::class, TourneyFixture::class, UserFixtures::class]);

        $service = self::getContainer()->get(TourneyService::class);
        $tourney = $service->getVisibleTourneys()[0];
        $this->assertEquals(TourneyRules::SingleElimination, $tourney->getMode());

        $this->assertEquals(TourneyStage::Finished, $tourney->getStatus());
        $podium = TourneyService::getPodium($tourney);
        $this->assertCount(3, $podium);
        $final = TourneyService::getFinal($tourney);
        $this->assertEquals($final->getWinner(), $podium[1][0]);
        $this->assertEquals($final->getLoser(), $podium[2][0]);
        $this->assertCount(2, $podium[3]);
    }

    public function testTourneyPodiumEmpty()
    {
        $this->databaseTool->loadFixtures([TourneyFixtureGames::class, TourneyFixture::class, UserFixtures::class]);

        $service = self::getContainer()->get(TourneyService::class);
        $tourney = $service->getVisibleTourneys()[2];

        $this->assertEquals(TourneyStage::Registration, $tourney->getStatus());
        $this->assertEmpty(TourneyService::getPodium($tourney));
    }

    public function testSameTeamMultipleTourneys()
    {
        $this->databaseTool->loadFixtures([TourneyFixture::class, UserFixtures::class]);

        $service = self::getContainer()->get(TourneyService::class);
        list($t1, $t2, $t3, $t4) = $service->getVisibleTourneys();
        $user = $this->getUser(10);

        $this->assertEquals('Chess 2v2', $t3->getName());
        $this->assertEquals('Rollerball', $t4->getName());

        $service->userRegister($t4, $user, 'Not so Pro Team');

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessageMatches('/name.*exists/');
        $this->expectExceptionCode(ServiceException::CAUSE_INCONSISTENT);
        $service->userRegister($t3, $user, 'Not so Pro Team');
    }

    public function testTourneySeedingSingleElimination()
    {
        $this->databaseTool->loadFixtures([TourneyFixtureGames::class, TourneyFixture::class, UserFixtures::class]);

        $service = self::getContainer()->get(TourneyService::class);
        $tourney = $service->getVisibleTourneys()[2];
        $tourney->setMode(TourneyRules::SingleElimination);
        $service->advance($tourney);

        $teams = $tourney->getTeams()->toArray();
        $this->assertCount(5, $teams);
        $seed = [$teams[2], $teams[1], $teams[3], $teams[0], $teams[4]];
        $service->seed($tourney, $seed);
        $this->assertCount(4, $tourney->getGames());
        $finale = TourneyService::getFinal($tourney);
        $this->assertNotEmpty($finale);
        list($g1, $g2) = $finale->getChildren();
        $this->assertNotEmpty($g1);
        $this->assertNotEmpty($g2);
        $this->assertCount(1, $g1->getChildren());
        list($g0) = $g1->getChildren();
        $this->assertEmpty($g0->getChildren());
        $this->assertEmpty($g2->getChildren());
        $this->assertTrue($g1->isChildA());
        $this->assertFalse($g0->isChildA());
        $this->assertFalse($g2->isChildA());
        $this->assertEquals($teams[0], $g0->getTeamA());
        $this->assertEquals($teams[4], $g0->getTeamB());
        $this->assertEquals($teams[2], $g1->getTeamA());
        $this->assertEmpty($g1->getTeamB());
        $this->assertEquals($teams[3], $g2->getTeamA());
        $this->assertEquals($teams[1], $g2->getTeamB());
    }

    public function testTourneySeedingDoubleElimination()
    {
        $this->databaseTool->loadFixtures([TourneyFixtureGames::class, TourneyFixture::class, UserFixtures::class]);

        $service = self::getContainer()->get(TourneyService::class);
        $tourney = $service->getVisibleTourneys()[2];
        $tourney->setMode(TourneyRules::DoubleElimination);
        $service->advance($tourney);

        $teams = $tourney->getTeams()->toArray();
        $this->assertCount(5, $teams);
        $seed = [$teams[2], $teams[1], $teams[3], $teams[0], $teams[4]];
        $service->seed($tourney, $seed);
        $this->assertCount(8, $tourney->getGames());
        $g8 = TourneyService::getFinal($tourney);
        $this->assertNotEmpty($g8);
        list($g5, $g7) = $g8->getChildren();
        // winner
        $this->assertCount(2, $g5->getChildren());
        list($g3, $g2) = $g5->getChildren();
        $this->assertCount(1, $g2->getChildren());
        $this->assertCount(0, $g3->getChildren());
        list($g1) = $g2->getChildren();
        $this->assertCount(0, $g1->getChildren());
        // loser
        $this->assertCount(1, $g7->getChildren());
        list($g6) = $g7->getChildren();
        $this->assertCount(1, $g6->getChildren());
        list($g4) = $g6->getChildren();
        $this->assertCount(0, $g4->getChildren());

        $this->assertEquals($g4, $g1->getLoserNext());
        $this->assertEquals($g4, $g3->getLoserNext());
        $this->assertTrue($g1->isIsLoserNextA());
        $this->assertFalse($g3->isIsLoserNextA());
        $this->assertEquals($g6, $g2->getLoserNext());
        $this->assertTrue($g2->isIsLoserNextA());
        $this->assertEquals($g7, $g5->getLoserNext());
        $this->assertFalse($g5->isIsLoserNextA());

        $this->assertEmpty($g4->getLoserNext());
        $this->assertEmpty($g6->getLoserNext());
        $this->assertEmpty($g7->getLoserNext());
        $this->assertEmpty($g8->getLoserNext());
    }

    private function seedTestTourney(): array
    {
        $service = self::getContainer()->get(TourneyService::class);
        $tourney = $service->getVisibleTourneys()[2];
        $tourney->setMode(TourneyRules::DoubleElimination);
        $service->advance($tourney);

        $teams = $tourney->getTeams()->toArray();
        $this->assertCount(5, $teams);
        $seed = [$teams[2], $teams[1], $teams[3], $teams[0], $teams[4]];
        $service->seed($tourney, $seed);
        $service->advance($tourney);
        return array($service, $tourney);
    }

    public function testTourneyRunDoubleElimination()
    {
        $this->databaseTool->loadFixtures([TourneyFixtureGames::class, TourneyFixture::class, UserFixtures::class]);

        /**
         * @var TourneyService $service
         * @var Tourney $tourney
         */
        list($service, $tourney) = $this->seedTestTourney();

        $this->assertEmpty(TourneyService::getPodium($tourney));

        $g8 = TourneyService::getFinal($tourney);
        list($g5, $g7) = $g8->getChildren();
        list($g3, $g2) = $g5->getChildren();
        list($g1) = $g2->getChildren();
        list($g6) = $g7->getChildren();
        list($g4) = $g6->getChildren();
        $games = [$g1, $g2, $g3, $g4, $g5, $g6, $g7, $g8];
        $this->assertEquals([false, false, false, false, false, false, false, false], array_map(fn (TourneyGame $g) => $g->isDone(), $games));
        $this->assertEquals([true,  false, true,  false, false, false, false, false], array_map(fn (TourneyGame $g) => $g->isPending(), $games));
        $service->logResult($g1, 1, 3);
        $this->assertEquals([true, false, false, false, false, false, false, false], array_map(fn (TourneyGame $g) => $g->isDone(), $games));
        $this->assertEquals([false, true, true,  false, false, false, false, false], array_map(fn (TourneyGame $g) => $g->isPending(), $games));
        $service->logResult($g3, 1, 0);
        $service->logResult($g2, 1, 2);
        $this->assertEquals([true, true, true, false, false, false, false, false], array_map(fn (TourneyGame $g) => $g->isDone(), $games));
        $this->assertEquals([false, false, false, true, true, false, false, false], array_map(fn (TourneyGame $g) => $g->isPending(), $games));

        // winner finale
        $this->assertEquals($g5->getTeamA(), $g3->getTeamA());
        $this->assertEquals($g5->getTeamB(), $g2->getTeamB());
        $service->logResult($g5, 1, 0);
        $this->assertEquals([false, false, false, true, false, false, false, false], array_map(fn (TourneyGame $g) => $g->isPending(), $games));

        // loser 1st game
        $this->assertEquals($g4->getTeamA(), $g1->getTeamA());
        $this->assertEquals($g4->getTeamB(), $g3->getTeamB());
        $service->logResult($g4, 1, 0);
        $this->assertEquals([false, false, false, false, false, true, false, false], array_map(fn (TourneyGame $g) => $g->isPending(), $games));

        // loser 2nd game
        $this->assertEquals($g6->getTeamA(), $g2->getTeamA());
        $this->assertEquals($g6->getTeamB(), $g4->getTeamA());
        $service->logResult($g6, 1, 0);
        $this->assertEquals([false, false, false, false, false, false, true, false], array_map(fn (TourneyGame $g) => $g->isPending(), $games));

        // loser finale
        $this->assertEquals($g7->getTeamA(), $g6->getTeamA());
        $this->assertEquals($g7->getTeamB(), $g5->getTeamB());
        $service->logResult($g7, 1, 0);
        $this->assertEquals([false, false, false, false, false, false, false, true], array_map(fn (TourneyGame $g) => $g->isPending(), $games));

        // finale
        $this->assertEquals($g8->getTeamA(), $g5->getTeamA());
        $this->assertEquals($g8->getTeamB(), $g7->getTeamA());
        $service->logResult($g8, 2, 1);

        // winner has won, tourney done
        $podium = $service::getPodium($tourney);
        $this->assertNotEmpty($podium);
        $this->assertEquals([$g8->getWinner()], $podium[1]);
        $this->assertEquals([$g8->getLoser()], $podium[2]);
        $this->assertEquals([$g7->getLoser()], $podium[3]);
    }

    public function testTourneyRunDoubleEliminationTwoFinale()
    {
        $this->databaseTool->loadFixtures([TourneyFixtureGames::class, TourneyFixture::class, UserFixtures::class]);

        /**
         * @var TourneyService $service
         * @var Tourney $tourney
         */
        list($service, $tourney) = $this->seedTestTourney();

        $this->assertEmpty(TourneyService::getPodium($tourney));

        $g8 = TourneyService::getFinal($tourney);
        list($g5, $g7) = $g8->getChildren();
        list($g3, $g2) = $g5->getChildren();
        list($g1) = $g2->getChildren();
        list($g6) = $g7->getChildren();
        list($g4) = $g6->getChildren();
        $games = [$g1, $g2, $g3, $g4, $g5, $g6, $g7, $g8];
        $service->logResult($g1, 1, 3);
        $service->logResult($g3, 1, 0);
        $service->logResult($g2, 1, 2);
        $service->logResult($g5, 1, 0);
        $service->logResult($g4, 1, 0);
        $service->logResult($g6, 1, 0);
        $service->logResult($g7, 1, 0);

        // finale
        $service->logResult($g8, 1, 2);
        // loser has won, tourney not done yet
        $this->assertEmpty(TourneyService::getPodium($tourney));
        $new_final = TourneyService::getFinal($tourney);
        $this->assertNotEquals($g8, $new_final);
        $service->logResult($new_final, 1, 2);

        $podium = $service::getPodium($tourney);
        $this->assertNotEmpty($podium);
        $this->assertEquals([$new_final->getWinner()], $podium[1]);
        $this->assertEquals([$new_final->getLoser()], $podium[2]);
        $this->assertEquals([$g7->getLoser()], $podium[3]);
    }

    public function testTourneyLogNotFullySeededGame()
    {
        $this->databaseTool->loadFixtures([TourneyFixtureGames::class, TourneyFixture::class, UserFixtures::class]);

        /**
         * @var TourneyService $service
         * @var Tourney $tourney
         */
        list($service, $tourney) = $this->seedTestTourney();
        $g8 = TourneyService::getFinal($tourney);
        list($g5, $g7) = $g8->getChildren();
        list($g3, $g2) = $g5->getChildren();
        list($g1) = $g2->getChildren();
        list($g6) = $g7->getChildren();
        list($g4) = $g6->getChildren();
        $this->assertNotNull($g2->getTeamB());
        $this->assertNull($g2->getTeamA());
        $this->expectException(ServiceException::class);
        $service->logResult($g2, 1,2);
    }

    public function testTourneyLogNotPendingGame()
    {
        $this->databaseTool->loadFixtures([TourneyFixtureGames::class, TourneyFixture::class, UserFixtures::class]);

        /**
         * @var TourneyService $service
         * @var Tourney $tourney
         */
        list($service, $tourney) = $this->seedTestTourney();
        $g8 = TourneyService::getFinal($tourney);
        list($g5, $g7) = $g8->getChildren();
        list($g3, $g2) = $g5->getChildren();
        list($g1) = $g2->getChildren();
        list($g6) = $g7->getChildren();
        list($g4) = $g6->getChildren();
        $this->assertNotNull($g1->getTeamA());
        $this->assertNotNull($g1->getTeamB());
        $this->assertTrue($g1->isPending());
        // initial result log
        $service->logResult($g1, 1,2);
        $this->assertTrue($g1->isDone());
        $this->assertFalse($g1->isPending());
        // admin may update game result
        $service->logResult($g1, 1,3);
        // user may not
        $user = $this->getUser(4);
        $this->expectException(ServiceException::class);
        $service->logResultUser($g1, $user, 1,5);
    }
}