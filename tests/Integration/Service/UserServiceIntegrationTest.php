<?php

namespace App\Tests\Integration\Service;

use App\Entity\Clan;
use App\Service\UserService;
use App\Tests\Integration\DatabaseTestCase;
use Ramsey\Uuid\Nonstandard\Uuid;
use Ramsey\Uuid\UuidInterface;

class UserServiceIntegrationTest extends DatabaseTestCase
{
    private static function i2u(int $i): string
    {
        return Uuid::fromInteger($i)->toString();
    }

    private function getUserData(): array
    {
        return [
            [[], []],
            [[self::i2u(6)], []],
            [[self::i2u(2), self::i2u(4), self::i2u(6)], [self::i2u(9), self::i2u(0x3EA)]],
            [[self::i2u(1), self::i2u(1), self::i2u(1)], [self::i2u(9)]]
        ];
    }

    private function getClanData(): array
    {
        return [
            [[]],
            [[self::i2u(0x3EA), self::i2u(0x3EF)]],
            [[self::i2u(0x3EA), self::i2u(0x3EA)]]
        ];
    }

    /**
     * @dataProvider getUserData
     */
    public function testGetUsers(array $uuids)
    {
        $userService = self::getContainer()->get(UserService::class);
        
        $users = $userService->getUsers($uuids);
        $this->assertIsArray($users);
        $this->assertSameSize($uuids, $users);
        for ($i = 0; $i < count($uuids); $i++) {
            $this->assertEquals($uuids[$i], $users[$i]->getUuid()->toString());
        }
    }

    /**
     * @dataProvider getUserData
     */
    public function testGetUsersAssoc(array $uuids)
    {
        $userService = self::getContainer()->get(UserService::class);

        $users = $userService->getUsers($uuids, true);
        $this->assertIsArray($users);
        $this->assertSameSize(array_unique($uuids), $users);
        foreach ($uuids as $s) {
            $this->assertArrayHasKey($s, $users);
            $this->assertEquals($s, $users[$s]->getUuid()->toString());
        }
    }

    /**
     * @dataProvider getClanData
     */
    public function testGetGetClans(array $uuids)
    {
        $userService = self::getContainer()->get(UserService::class);

        $clans = $userService->getClans($uuids);

        $this->assertIsArray($clans);
        $this->assertSameSize($uuids, $clans);

        for ($i = 0; $i < count($uuids); $i++) {
            $this->assertEquals($uuids[$i], $clans[$i]->getUuid()->toString());
        }
    }

    /**
     * @dataProvider getClanData
     */
    public function testGetGetClansAssoc(array $uuids)
    {
        $userService = self::getContainer()->get(UserService::class);

        $clans = $userService->getClans($uuids, true);

        $this->assertIsArray($clans);
        $this->assertSameSize(array_unique($uuids), $clans);

        foreach ($uuids as $s) {
            $this->assertArrayHasKey($s, $clans);
            $this->assertEquals($s, $clans[$s]->getUuid()->toString());
        }
    }

    /**
     * @dataProvider getUserData
     */
    public function testGetClansByUser(array $uuids, array $clanUuids)
    {
        $userService = self::getContainer()->get(UserService::class);

        $clans = $userService->getClansByUsers($uuids);

        $this->assertIsArray($clans);
        $this->assertSameSize($clanUuids, $clans);

        foreach ($clanUuids as $s) {
            $this->assertContains($s, array_map(fn (Clan $c) => $c->getUuid()->toString(), $clans));
        }
    }

    /**
     * @dataProvider getUserData
     */
    public function testGetClansByUserAssoc(array $uuids, array $clanUuids)
    {
        $userService = self::getContainer()->get(UserService::class);

        $clans = $userService->getClansByUsers($uuids, true);

        $this->assertIsArray($clans);
        $this->assertSameSize($clanUuids, $clans);

        foreach ($clanUuids as $s) {
            $this->assertArrayHasKey($s, $clans);
            $this->assertEquals($s, $clans[$s]->getUuid()->toString());
        }
    }
}