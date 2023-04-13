<?php

namespace App\Tests\Unit\Idm;

use App\Entity\User;
use App\Idm\IdmManager;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class IdmManagerTest extends TestCase
{
    public function testSingleUserRequest()
    {
        $mockResponse = new MockResponse('{"email":"user1@localhost.local","emailConfirmed":false,"infoMails":false,"nickname":"User 1","firstname":"User","surname":"Eins","gender":"f","personalDataConfirmed":true,"personalDataLocked":false,"isSuperadmin":false,"registeredAt":"2023-04-11T06:27:45+02:00","modifiedAt":"2023-04-11T06:27:45+02:00","id":676,"uuid":"00000000-0000-0000-0000-000000000001","clans":[{"id":3906,"uuid":"00000000-0000-0000-0000-0000000003e9","name":"Clan 1","createdAt":"2023-04-11T06:27:49+02:00","modifiedAt":"2023-04-11T06:27:49+02:00","clantag":"CL1","website":"http:\/\/localhost","description":"wubwub","users":[{"uuid":"00000000-0000-0000-0000-000000000001"},{"uuid":"00000000-0000-0000-0000-000000000002"}],"admins":[{"uuid":"00000000-0000-0000-0000-000000000001"}]}]}');
        $mockClient = new MockHttpClient($mockResponse);
        $mockLogger = $this->createMock(LoggerInterface::class);
        $manager = new IdmManager($mockClient, $mockLogger);
        $user = $manager->request(User::class, Uuid::fromInteger(strval(4)));
        $this->assertInstanceOf(User::class, $user);
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
        $this->assertEquals(Uuid::fromInteger(strval(4)), $user->getUuid());
        $this->assertEquals(676, $user->getId());
        $this->assertEquals(new \DateTime('2023-04-11T06:27:45+02:00'), $user->getRegisteredAt());
        $this->assertCount(1, $user->getClans());
        // TODO assert clan
        // TODO assert other timestamps

        $this->assertEquals(1, $mockClient->getRequestsCount());
        $this->assertEquals('GET', $mockResponse->getRequestMethod());
    }
}