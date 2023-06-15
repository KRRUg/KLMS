<?php

namespace App\Tests\Unit\Idm;

use App\Entity\User;
use App\Idm\IdmManager;
use App\Tests\IdmServerMock;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\MockHttpClient;

class IdmRepositoryTest extends TestCase
{
    public function testRepositoryNoFilter()
    {
        $mock = new IdmServerMock();
        $mockClient = new MockHttpClient($mock);
        $mockLogger = $this->createMock(LoggerInterface::class);
        $manager = new IdmManager($mockClient, $mockLogger);
        $repo = $manager->getRepository(User::class);
        $users = $repo->findAll();

        $this->assertEquals(0, $mock->getInvalidCalls());
        $this->assertCount(21, $users);
        $this->assertEquals(1, $mock->countRequests());

        $request = $mock->getLastRequest();
        $this->assertEquals('users', $request->class);
        $this->assertEmpty($request->uuid);
        $this->assertTrue($request->paramNotExists('exact'));
        $this->assertTrue($request->paramNotExists('case'));
    }

    public function testRepositoryFindOneBy()
    {
        $mock = new IdmServerMock();
        $mockClient = new MockHttpClient($mock);
        $mockLogger = $this->createMock(LoggerInterface::class);
        $manager = new IdmManager($mockClient, $mockLogger);
        $repo = $manager->getRepository(User::class);
        $users = $repo->findBy(['surname' => 'Drei']);

        $this->assertEquals(0, $mock->getInvalidCalls());
        $this->assertCount(1, $users);
        $this->assertEquals(1, $mock->countRequests());

        $request = $mock->getLastRequest();
        $this->assertEquals('users', $request->class);
        $this->assertEmpty($request->uuid);
        $this->assertTrue($request->paramHasValue('exact', 'true'));
        $this->assertTrue($request->paramHasValue('case', 'true'));
    }

    public function testRepositoryFindOneByWrongCase()
    {
        $mock = new IdmServerMock();
        $mockClient = new MockHttpClient($mock);
        $mockLogger = $this->createMock(LoggerInterface::class);
        $manager = new IdmManager($mockClient, $mockLogger);
        $repo = $manager->getRepository(User::class);
        $users = $repo->findBy(['surname' => 'drei']);

        $this->assertEquals(0, $mock->getInvalidCalls());
        $this->assertEquals(1, $mock->countRequests());
        $this->assertCount(0, $users);

        $request = $mock->getLastRequest();
        $this->assertEquals('users', $request->class);
        $this->assertEmpty($request->uuid);
        $this->assertTrue($request->paramHasValue('exact', 'true'));
        $this->assertTrue($request->paramHasValue('case', 'true'));
    }

    public function testRepositoryFindFuzzy()
    {
        $mock = new IdmServerMock();
        $mockClient = new MockHttpClient($mock);
        $mockLogger = $this->createMock(LoggerInterface::class);
        $manager = new IdmManager($mockClient, $mockLogger);
        $repo = $manager->getRepository(User::class);
        $users = $repo->findFuzzy('Drei');

        $this->assertCount(1, $users);
        $this->assertEquals(1, $mock->countRequests());

        $request = $mock->getLastRequest();
        $this->assertEquals('users', $request->class);
        $this->assertEmpty($request->uuid);
        $this->assertTrue($request->paramHasValue('exact', 'false'));
        $this->assertTrue($request->paramHasValue('case', 'false'));
    }

    public function testRepositoryFindFuzzyMultiple()
    {
        $mock = new IdmServerMock();
        $mockClient = new MockHttpClient($mock);
        $mockLogger = $this->createMock(LoggerInterface::class);
        $manager = new IdmManager($mockClient, $mockLogger);
        $repo = $manager->getRepository(User::class);
        $users = $repo->findFuzzy("User");

        $this->assertCount(20, $users);
        $this->assertEquals(1, $mock->countRequests());

        $request = $mock->getLastRequest();
        $this->assertEquals('users', $request->class);
        $this->assertEmpty($request->uuid);
        $this->assertTrue($request->paramHasValue('exact', 'false'));
        $this->assertTrue($request->paramHasValue('case', 'false'));
    }
}