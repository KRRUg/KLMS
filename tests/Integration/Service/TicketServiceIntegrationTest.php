<?php

namespace App\Tests\Integration\Service;

use App\DataFixtures\ShopFixture;
use App\Entity\User;
use App\Idm\IdmManager;
use App\Service\TicketService;
use App\Service\TicketState;
use App\Tests\Integration\DatabaseTestCase;
use Ramsey\Uuid\Nonstandard\Uuid;

class TicketServiceIntegrationTest extends DatabaseTestCase
{
    private function getUser(int $id): ?User
    {
        $manager = self::getContainer()->get(IdmManager::class);
        $userRepo = $manager->getRepository(User::class);
        return $userRepo->findOneById(Uuid::fromInteger($id));
    }

    public function testTicketCreation()
    {
        $this->databaseTool->loadFixtures([ShopFixture::class]);
        $tickteService = self::getContainer()->get(TicketService::class);

        $c = $tickteService->countTickets();
        $cf = $tickteService->countFreeTickets();
        $cp = $tickteService->countPunchedTickets();
        $t = $tickteService->createTicket();
        $this->assertEquals(TicketState::NEW, $t->getState());
        $this->assertEquals($c + 1, $tickteService->countTickets());
        $this->assertEquals($cf + 1, $tickteService->countFreeTickets());
        $this->assertEquals($cp, $tickteService->countPunchedTickets());
        $this->assertFalse($tickteService->hasInvalid());
    }

    private function provideUsers(): array
    {
        return [
            // userid, hasTicket, hasPunchedTicket
            [2,         true,       true],
            [14,        true,      false],
            [11,       false,      false],
        ];
    }

    private function provideTickets(): array
    {
        return [
            // ticketCode,         isRedeemed, isPunched
            ['CODE1-KRRUG-BBBBB',  false,      false],
            ['CODE1-KRRUG-AAAAA',  true,       false],
            ['00000-KRRUG-NR001',  true,       true],
        ];
    }

    /**
     * @dataProvider provideUsers
     */
    public function testUserRegistration(int $uid, bool $registered, bool $punched)
    {
        $this->databaseTool->loadFixtures([ShopFixture::class]);
        $tickteService = self::getContainer()->get(TicketService::class);
        $user = $this->getUser($uid);

        $this->assertEquals($registered, $tickteService->isUserRegistered($user));
        $c = $tickteService->countTickets();
        $cf = $tickteService->countFreeTickets();
        $cp = $tickteService->countPunchedTickets();

        $tickteService->registerUser($user);

        $this->assertTrue($tickteService->isUserRegistered($user));
        $this->assertEquals($c + (!$registered ? 1 : 0), $tickteService->countTickets());
        $this->assertEquals($cf, $tickteService->countFreeTickets());
        $this->assertEquals($cp, $tickteService->countPunchedTickets());
        $this->assertFalse($tickteService->hasInvalid());
    }

    /**
     * @dataProvider provideUsers
     */
    public function testUserUnRegistration(int $uid, bool $registered, bool $punched)
    {
        $this->databaseTool->loadFixtures([ShopFixture::class]);
        $tickteService = self::getContainer()->get(TicketService::class);
        $user = $this->getUser($uid);

        $this->assertEquals($registered, $tickteService->isUserRegistered($user));
        $this->assertEquals($punched, $tickteService->isUserPunched($user));
        $c = $tickteService->countTickets();
        $cf = $tickteService->countFreeTickets();
        $cp = $tickteService->countPunchedTickets();

        $r = $tickteService->unregisterUser($user);

        $this->assertEquals($registered, $r);
        $this->assertFalse($tickteService->isUserRegistered($user));
        $this->assertEquals($c - ($registered ? 1 : 0), $tickteService->countTickets());
        $this->assertEquals($cf, $tickteService->countFreeTickets());
        $this->assertEquals($cp - ($punched ? 1 : 0), $tickteService->countPunchedTickets());
        $this->assertFalse($tickteService->hasInvalid());
    }

    /**
     * @dataProvider provideUsers
     */
    public function testUserUnRegistrationKeep(int $uid, bool $registered, bool $punched)
    {
        $this->databaseTool->loadFixtures([ShopFixture::class]);
        $tickteService = self::getContainer()->get(TicketService::class);
        $user = $this->getUser($uid);

        $this->assertEquals($registered, $tickteService->isUserRegistered($user));
        $this->assertEquals($punched, $tickteService->isUserPunched($user));
        $c = $tickteService->countTickets();
        $cf = $tickteService->countFreeTickets();
        $cp = $tickteService->countPunchedTickets();

        $r = $tickteService->unregisterUser($user, false);

        $this->assertEquals($registered, $r);
        $this->assertFalse($tickteService->isUserRegistered($user));
        $this->assertEquals($c, $tickteService->countTickets());
        $this->assertEquals($cf + ($registered ? 1 : 0), $tickteService->countFreeTickets());
        $this->assertEquals($cp - ($punched ? 1 : 0), $tickteService->countPunchedTickets());
        $this->assertFalse($tickteService->hasInvalid());
    }

    /**
     * @dataProvider provideUsers
     */
    public function testRedeemTicket(int $uid, bool $registered, bool $punched)
    {
        $this->databaseTool->loadFixtures([ShopFixture::class]);
        $tickteService = self::getContainer()->get(TicketService::class);
        $user = $this->getUser($uid);
        $ticketCode = 'CODE1-KRRUG-BBBBB';

        $this->assertEquals($registered, $tickteService->isUserRegistered($user));
        $this->assertTrue($tickteService->ticketCodeUnused($ticketCode));
        $c = $tickteService->countTickets();
        $cf = $tickteService->countFreeTickets();
        $cp = $tickteService->countPunchedTickets();

        $r = $tickteService->redeemTicket($ticketCode, $user);
        $this->assertEquals(!$registered, $r);
        $this->assertTrue($tickteService->isUserRegistered($user));
        $this->assertEquals($c, $tickteService->countTickets());
        $this->assertEquals($cf - (!$registered ? 1 : 0), $tickteService->countFreeTickets());
        $this->assertEquals($cp, $tickteService->countPunchedTickets());
        $this->assertFalse($tickteService->hasInvalid());
    }

    /**
     * @dataProvider provideUsers
     */
    public function testPunchTicketUser(int $uid, bool $registered, bool $punched)
    {
        $this->databaseTool->loadFixtures([ShopFixture::class]);
        $tickteService = self::getContainer()->get(TicketService::class);
        $user = $this->getUser($uid);

        $this->assertEquals($registered, $tickteService->isUserRegistered($user));
        $this->assertEquals($punched, $tickteService->isUserPunched($user));
        $c = $tickteService->countTickets();
        $cf = $tickteService->countFreeTickets();
        $cp = $tickteService->countPunchedTickets();
        $expectSuccess = $registered && !$punched;

        $r = $tickteService->punchTicketUser($user);

        $this->assertEquals($expectSuccess, $r);
        $this->assertEquals($registered, $tickteService->isUserPunched($user));
        $this->assertEquals($c, $tickteService->countTickets());
        $this->assertEquals($cf, $tickteService->countFreeTickets());
        $this->assertEquals($cp + ($expectSuccess ? 1 : 0), $tickteService->countPunchedTickets());
        $this->assertFalse($tickteService->hasInvalid());
    }

    /**
     * @dataProvider provideTickets
     */
    public function testPunchTicketCode(string $code, bool $redeemed, bool $punched)
    {
        $this->databaseTool->loadFixtures([ShopFixture::class]);
        $tickteService = self::getContainer()->get(TicketService::class);
        $ticket = $tickteService->getTicketCode($code);
        $this->assertNotNull($ticket);
        $this->assertEquals($redeemed, $ticket->isRedeemed());
        $this->assertEquals($punched, $ticket->isPunched());
        $expectSuccess = $redeemed && !$punched;

        $c = $tickteService->countTickets();
        $cf = $tickteService->countFreeTickets();
        $cp = $tickteService->countPunchedTickets();

        $r = $tickteService->punchTicketCode($code);

        $this->assertEquals($expectSuccess, $r);
        $this->assertEquals($redeemed, $ticket->isPunched());
        $this->assertEquals($c, $tickteService->countTickets());
        $this->assertEquals($cf, $tickteService->countFreeTickets());
        $this->assertEquals($cp + ($expectSuccess ? 1 : 0), $tickteService->countPunchedTickets());
        $this->assertFalse($tickteService->hasInvalid());
    }
}