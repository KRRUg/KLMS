<?php

namespace App\Tests\Functional;

use App\Tests\IdmServerMock;
use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Liip\TestFixturesBundle\Services\DatabaseTools\AbstractDatabaseTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class DatabaseWebTestCase extends WebTestCase
{
    protected readonly AbstractDatabaseTool $databaseTool;

    protected readonly KernelBrowser $client;

    protected readonly IdmServerMock $mock;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = self::createClient();

        // don't reboot the kernel after one request to avoid trashing of in-memory db
        $this->client->disableReboot();
        $this->databaseTool = static::getContainer()->get(DatabaseToolCollection::class)->get();
        $mock = static::getContainer()->get(IdmServerMock::class);
        $this->assertInstanceOf(IdmServerMock::class, $mock);
        $this->mock = $mock;
    }
}