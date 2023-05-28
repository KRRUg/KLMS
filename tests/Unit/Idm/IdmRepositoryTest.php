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

        $this->assertCount(21, $users);
    }

    public function testRepositoryFindOneBy()
    {
        $mock = new IdmServerMock();
        $mockClient = new MockHttpClient($mock);
        $mockLogger = $this->createMock(LoggerInterface::class);
        $manager = new IdmManager($mockClient, $mockLogger);
        $repo = $manager->getRepository(User::class);
        $users = $repo->findBy(['surname' => 'Drei']);

        $this->assertCount(1, $users);
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
    }
}