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

        $this->client->request('GET', '/logout');
        $this->assertResponseStatusCodeSame(200);
        $this->assertSelectorTextContains('#dropdownMenuUser', "Anmelden");
    }
}