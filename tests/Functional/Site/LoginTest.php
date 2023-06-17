<?php

namespace App\Tests\Functional\Site;

use App\Tests\Functional\DatabaseWebTestCase;

class LoginTest extends DatabaseWebTestCase
{
    public function testLoginAndLogout()
    {
        $this->databaseTool->loadFixtures([]);
        $this->client->followRedirects();

        $crawler = $this->client->request('GET', '/');
        $this->assertResponseStatusCodeSame(200);
        $this->assertSelectorTextContains('#dropdownMenuUser', "Anmelden");

        $form = $crawler->selectButton('Login')->form();
        $form['username']->setValue('user2@localhost.local');
        $form['password']->setValue('user2');
        $this->client->submit($form);

        $this->assertResponseStatusCodeSame(200);
        $this->assertSelectorTextContains('#dropdownMenuUser', "User 2");
        $this->assertSelectorNotExists('.alert-error');

        $this->client->request('GET', '/logout');
        $this->assertResponseStatusCodeSame(200);
        $this->assertSelectorTextContains('#dropdownMenuUser', "Anmelden");
    }

    public function testLoginUserNotFound()
    {
        $this->databaseTool->loadFixtures([]);

        $crawler = $this->client->request('GET', '/');
        $this->assertResponseStatusCodeSame(200);
        $this->assertSelectorTextContains('#dropdownMenuUser', "Anmelden");

        $form = $crawler->selectButton('Login')->form();
        $form['username']->setValue('unkown@localhost.local');
        $form['password']->setValue('pa$$word');
        $this->client->submit($form);

        $this->assertResponseStatusCodeSame(302);
        $this->assertResponseRedirects('/login');
        $this->client->followRedirect();
        $this->assertSelectorTextContains('#dropdownMenuUser', "Anmelden");
        $this->assertSelectorExists('.alert-error');
    }

    public function testLoginNotConfirmedUser()
    {
        $this->databaseTool->loadFixtures([]);

        $crawler = $this->client->request('GET', '/');
        $this->assertResponseStatusCodeSame(200);

        $form = $crawler->selectButton('Login')->form();
        $form['username']->setValue('user1@localhost.local');
        $form['password']->setValue('pa$$word');
        $this->client->submit($form);

        $this->assertResponseStatusCodeSame(302);
        $this->assertResponseRedirects('/login');
        $this->client->followRedirect();
        $this->assertSelectorTextContains('#dropdownMenuUser', "Anmelden");
        $this->assertSelectorExists('.alert-error');
    }
}