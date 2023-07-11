<?php

namespace App\Tests\Integration\Service;

use App\DataFixtures\TourneyFixture;
use App\DataFixtures\UserFixtures;
use App\Entity\Tourney;
use App\Entity\TourneyStatus;
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
        $this->assertEquals(TourneyStatus::Registration, $tourney->getStatus());
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
        $this->assertEquals(TourneyStatus::Registration, $tourney->getStatus());
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
        $this->assertEquals(TourneyStatus::Registration, $tourney->getStatus());
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
        $this->assertEquals(TourneyStatus::Registration, $tourney->getStatus());
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
        $this->assertEquals(TourneyStatus::Running, $tourney->getStatus());

        $this->expectException(ServiceException::class);
        $service->userRegister($tourney, $user7, null);
    }

    public function testRegisterAlreadyRegistered()
    {
        $this->databaseTool->loadFixtures([TourneyFixture::class, UserFixtures::class]);
        $service = self::getContainer()->get(TourneyService::class);
        $user7 = $this->getUser(7);

        $tourney = $service->getVisibleTourneys()[1];
        $this->assertEquals(TourneyStatus::Registration, $tourney->getStatus());

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
        $this->assertEquals(TourneyStatus::Registration, $tourney->getStatus());

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
        $this->assertEquals(TourneyStatus::Registration, $tourney->getStatus());
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
        $this->assertEquals(TourneyStatus::Registration, $tourney->getStatus());
        $this->expectException(ServiceException::class);
        $service->userUnregister($tourney, $user18);
    }

    public function testRegisterNotParticipatingUser()
    {
        $this->databaseTool->loadFixtures([TourneyFixture::class, UserFixtures::class]);
        $service = self::getContainer()->get(TourneyService::class);

        $user0 = $this->getUser(0);
        $tourney = $service->getVisibleTourneys()[1];
        $this->assertEquals(TourneyStatus::Registration, $tourney->getStatus());
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
        $this->assertTrue($tourney->getStatus() == TourneyStatus::Running);

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
        $service->tourneyAdvance($tourney);

        $user7 = $this->getUser(7);

        $game = $service->getGameByTourneyAndUser($tourney, $user7);
        $this->assertNotEmpty($game);

        $this->expectException(ServiceException::class);
        $this->expectExceptionMessageMatches('/not running/i');
        $service->logResultUser($game, $user7, 1, 2);
    }
}