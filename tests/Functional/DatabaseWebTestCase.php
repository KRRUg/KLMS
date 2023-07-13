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
        $this->client->followRedirects();

        // don't reboot the kernel after one request to avoid trashing of in-memory db
        $this->client->disableReboot();
        $this->databaseTool = static::getContainer()->get(DatabaseToolCollection::class)->get();
        $mock = static::getContainer()->get(IdmServerMock::class);
        $this->assertInstanceOf(IdmServerMock::class, $mock);
        $this->mock = $mock;
    }

    protected function login(string $user): void
    {
        $csrfToken = $this->client->getContainer()->get('security.csrf.token_manager')->getToken('authenticate');
        $this->client->request('POST', '/login', [
            '_csrf_token' => $csrfToken,
            'username' => $user,
            'password' => 'password',
        ]);
        $this->assertResponseStatusCodeSame(200);
        $this->assertSelectorTextNotContains('#dropdownMenuUser', "Anmelden");
    }

    protected function logout(): void
    {
        $this->client->request('GET', '/logout');
        $this->assertResponseStatusCodeSame(200);
        $this->assertSelectorTextContains('#dropdownMenuUser', "Anmelden");
    }
}