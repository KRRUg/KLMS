<?php

namespace App\Tests\Unit\Idm;

use App\Entity\User;
use App\Idm\IdmManager;
use App\Tests\IdmServerMock;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Nonstandard\Uuid;
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
        $this->assertEmpty($request->id);
        $this->assertTrue($request->paramNotExists('exact'));
        $this->assertTrue($request->paramNotExists('case'));
    }

    public function testFindById()
    {
        $mock = new IdmServerMock();
        $mockClient = new MockHttpClient($mock);
        $mockLogger = $this->createMock(LoggerInterface::class);
        $manager = new IdmManager($mockClient, $mockLogger);
        $repo = $manager->getRepository(User::class);

        $uuid = Uuid::fromInteger(1);
        $user = $repo->findOneById($uuid);

        $this->assertEquals(0, $mock->getInvalidCalls());
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('user1@localhost.local', $user->getEmail());
        $this->assertEquals(1, $mock->countRequests());
    }

    public function testRepositoryFindBy()
    {
        $mock = new IdmServerMock();
        $mockClient = new MockHttpClient($mock);
        $mockLogger = $this->createMock(LoggerInterface::class);
        $manager = new IdmManager($mockClient, $mockLogger);
        $repo = $manager->getRepository(User::class);
        $users = $repo->findBy(['surname' => 'Drei']);

        $this->assertEquals(0, $mock->getInvalidCalls());
        $this->assertCount(1, $users);
        $user = $users[0];
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('user3@localhost.local', $user->getEmail());
        $this->assertEquals(1, $mock->countRequests());

        $request = $mock->getLastRequest();
        $this->assertEquals('users', $request->class);
        $this->assertEmpty($request->id);
        $this->assertTrue($request->paramHasValue('exact', 'true'));
        $this->assertTrue($request->paramHasValue('case', 'true'));
    }

    public function testRepositoryFindByMultipleParameter()
    {
        $mock = new IdmServerMock();
        $mockClient = new MockHttpClient($mock);
        $mockLogger = $this->createMock(LoggerInterface::class);
        $manager = new IdmManager($mockClient, $mockLogger);
        $repo = $manager->getRepository(User::class);
        $users = $repo->findBy(['firstname' => 'User', 'surname' => 'Drei']);

        $this->assertEquals(0, $mock->getInvalidCalls());
        $this->assertCount(1, $users);
        $user = $users[0];
        $this->assertInstanceOf(User::class, $user);
        $this->assertEquals('user3@localhost.local', $user->getEmail());
        $this->assertEquals(1, $mock->countRequests());

        $request = $mock->getLastRequest();
        $this->assertEquals('users', $request->class);
        $this->assertEmpty($request->id);
        $this->assertTrue($request->paramHasValue('exact', 'true'));
        $this->assertTrue($request->paramHasValue('case', 'true'));
    }

    public function testRepositoryFindByMultipleResults()
    {
        $mock = new IdmServerMock();
        $mockClient = new MockHttpClient($mock);
        $mockLogger = $this->createMock(LoggerInterface::class);
        $manager = new IdmManager($mockClient, $mockLogger);
        $repo = $manager->getRepository(User::class);
        $users = $repo->findBy(['firstname' => 'User']);

        $this->assertEquals(0, $mock->getInvalidCalls());
        $this->assertCount(20, $users);

        $request = $mock->getLastRequest();
        $this->assertEquals('users', $request->class);
        $this->assertEmpty($request->id);
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
        $this->assertEmpty($request->id);
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
        $this->assertEmpty($request->id);
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
        $this->assertEmpty($request->id);
        $this->assertTrue($request->paramHasValue('exact', 'false'));
        $this->assertTrue($request->paramHasValue('case', 'false'));
    }

    public function testBulkRequest()
    {
        $mock = new IdmServerMock();
        $mockClient = new MockHttpClient($mock);
        $mockLogger = $this->createMock(LoggerInterface::class);
        $manager = new IdmManager($mockClient, $mockLogger);
        $repo = $manager->getRepository(User::class);
        $uuids = [Uuid::fromInteger(4), Uuid::fromInteger(5), Uuid::fromInteger(4), Uuid::fromInteger(9)];
        $users = $repo->findById($uuids);
        $this->assertEquals(0, $mock->getInvalidCalls());
        $this->assertEquals(1, $mock->countRequests());
        $this->assertCount(4, $users);
        $this->assertInstanceOf(User::class, $users[0]);
        $this->assertInstanceOf(User::class, $users[1]);
        $this->assertInstanceOf(User::class, $users[2]);
        $this->assertInstanceOf(User::class, $users[3]);
        $this->assertEquals('user4@localhost.local', $users[0]->getEmail());
        $this->assertEquals('user5@localhost.local', $users[1]->getEmail());
        $this->assertEquals('user4@localhost.local', $users[2]->getEmail());
        $this->assertEquals('user9@localhost.local', $users[3]->getEmail());
        $this->assertTrue($users[0] === $users[2]);
    }
}