<?php

namespace App\Tests\Unit\Idm;

use App\Entity\Clan;
use App\Entity\User;
use App\Idm\IdmManager;
use App\Tests\IdmServerMock;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpClient\MockHttpClient;

class IdmManagerTest extends TestCase
{
    private function createManager(bool $answerWithDetails = true): array
    {
        $mock = new IdmServerMock($answerWithDetails);
        $mockClient = new MockHttpClient($mock);
        $mockLogger = $this->createMock(LoggerInterface::class);
        $manager = new IdmManager($mockClient, $mockLogger);
        return array($mock, $manager);
    }

    public function testOneRequestPerUserUuid()
    {
        list($mock, $manager) = $this->createManager();
        $user1 = $manager->request(User::class, Uuid::fromInteger(strval(1)));
        $user2 = $manager->request(User::class, Uuid::fromInteger(strval(1)));

        $this->assertEquals(0, $mock->getInvalidCalls());
        $this->assertEquals(1, $mock->countRequests());
        $this->assertEquals(1, $mock->countRequests('GET'));

        $this->assertInstanceOf(User::class, $user1);
        $this->assertInstanceOf(User::class, $user2);
        $this->assertTrue($user1 === $user2);
    }

    public function testTwoRequestsForTwoUsers()
    {
        list($mock, $manager) = $this->createManager();
        $user1 = $manager->request(User::class, Uuid::fromInteger(strval(1)));
        $user2 = $manager->request(User::class, Uuid::fromInteger(strval(2)));

        $this->assertEquals(0, $mock->getInvalidCalls());
        $this->assertEquals(2, $mock->countRequests());
        $this->assertEquals(2, $mock->countRequests('GET'));

        $this->assertInstanceOf(User::class, $user1);
        $this->assertInstanceOf(User::class, $user2);
        $this->assertTrue($user1 !== $user2);
    }

    public function testSingleUserRequest()
    {
        list($mock, $manager) = $this->createManager();
        $user = $manager->request(User::class, Uuid::fromInteger(strval(1)));

        $this->assertEquals(0, $mock->getInvalidCalls());
        $this->assertEquals(1, $mock->countRequests());
        $this->assertEquals(1, $mock->countRequests('GET'));

        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals(676, $user->getId());
        $this->assertTrue(Uuid::fromInteger(strval(1))->equals($user->getUuid()));
        $this->assertEquals('user1@localhost.local', $user->getEmail());
        $this->assertEquals('User 1', $user->getNickname());
        $this->assertEquals('User', $user->getFirstname());
        $this->assertEquals('Eins', $user->getSurname());
        $this->assertEquals('f', $user->getGender());
        $this->assertFalse($user->getEmailConfirmed());
        $this->assertFalse($user->getInfoMails());
        $this->assertFalse($user->getPersonalDataLocked());
        $this->assertFalse($user->getIsSuperadmin());
        $this->assertTrue($user->getPersonalDataConfirmed());
        $this->assertEquals(new \DateTime('2023-04-11T06:27:45+02:00'), $user->getRegisteredAt());
        $this->assertEquals(new \DateTime('2023-04-11T06:28:12+02:00'), $user->getModifiedAt());
        $this->assertCount(1, $user->getClans());
        $clan = $user->getClans()[0];
        $this->assertInstanceOf(Clan::class, $clan);
        $this->assertEquals(123, $clan->getId());
        $this->assertTrue(Uuid::fromInteger(strval(9))->equals($clan->getUuid()));
        $this->assertEquals("Clan 1", $clan->getName());
        $this->assertEquals("CL1", $clan->getClanTag());
        $this->assertEquals("wubwub", $clan->getDescription());
        $this->assertNull($clan->getJoinPassword());
        $this->assertEquals(new \DateTime('2023-04-10T19:39:35+0200'), $clan->getCreatedAt());
        $this->assertEquals(new \DateTime('2023-04-10T19:39:35+0200'), $clan->getModifiedAt());
        $this->assertTrue($clan->isAdmin($user));
        $this->assertCount(2, $clan->getUsers());
    }

    public function testSingleClanRequest()
    {
        list($mock, $manager) = $this->createManager();
        $clan = $manager->request(Clan::class, Uuid::fromInteger(strval(9)));

        $this->assertInstanceOf(Clan::class, $clan);
        $this->assertEquals('CL1', $clan->getClantag());
        $this->assertEquals('Clan 1', $clan->getName());
        $this->assertEquals('wubwub', $clan->getDescription());
        $this->assertNull($clan->getJoinPassword());
        $this->assertEquals(123, $clan->getId());
        $this->assertTrue(Uuid::fromInteger(strval(9))->equals($clan->getUuid()));
    }

    public function testAllUserRequest()
    {
        list($mock, $manager) = $this->createManager();
        $repo = $manager->getRepository(User::class);
        $users = $repo->findAll();

        $this->assertCount(21, $users);
        $this->assertEquals(0, $mock->getInvalidCalls());
        $this->assertEquals(1, $mock->countRequests());
        $this->assertEquals(1, $mock->countRequests('GET'));
    }

    public function testLazyAssociationLoadUser()
    {
        list($mock, $manager) = $this->createManager(false);
        $user = $manager->request(User::class, Uuid::fromInteger(strval(1)));

        // load user without clan objects
        $this->assertEquals(0, $mock->getInvalidCalls());
        $this->assertEquals(1, $mock->countRequests());
        $this->assertEquals(1, $mock->countRequests('GET'));
        $this->assertInstanceOf(User::class, $user);

        $clans = $user->getClans();
        // no load yet
        $this->assertCount(1, $clans);
        $this->assertEquals(1, $mock->countRequests());

        $clan = $clans[0];
        // load clan now
        $this->assertInstanceOf(Clan::class, $clan);
        $this->assertEquals(0, $mock->getInvalidCalls());
        $this->assertEquals(2, $mock->countRequests());
        $this->assertEquals(2, $mock->countRequests('GET'));
    }
}