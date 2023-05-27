<?php

namespace App\Tests\Functional;

use Liip\TestFixturesBundle\Services\DatabaseToolCollection;
use Liip\TestFixturesBundle\Services\DatabaseTools\AbstractDatabaseTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class DatabaseWebTestCase extends WebTestCase
{
    protected AbstractDatabaseTool $databaseTool;

    protected KernelBrowser $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = self::createClient();

        // don't reboot the kernel after one request to avoid trashing of in-memory db
        $this->client->disableReboot();
        $this->databaseTool = static::getContainer()->get(DatabaseToolCollection::class)->get();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        unset($this->databaseTool);
    }
}