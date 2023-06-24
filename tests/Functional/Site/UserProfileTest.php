<?php

namespace App\Tests\Functional\Site;

use App\Tests\Functional\DatabaseWebTestCase;

class UserProfileTest extends DatabaseWebTestCase
{
    public function testProfile()
    {
        $this->databaseTool->loadFixtures();
        $this->login('user2@localhost.local');

        $this->client->request('GET', '/user/profile');
        $this->assertResponseStatusCodeSame(200);
        $this->assertSelectorTextContains('h1.card-title', 'User 2');
        $this->assertSelectorTextContains('h3 ~ div', 'Clan 1');
        $this->assertSelectorTextContains('h3 ~ div', 'Clan 2');
    }

    public function testProfileEdit()
    {
        $this->databaseTool->loadFixtures();
        $this->login('user2@localhost.local');

        $this->client->request('GET', '/user/profile/edit');
        $this->assertResponseStatusCodeSame(200);
        $this->assertFormValue('form[name="user"]', 'user[nickname]', 'User 2');
        $this->assertFormValue('form[name="user"]', 'user[firstname]', 'User');
        $this->assertFormValue('form[name="user"]', 'user[surname]', 'Zwei');
        $this->assertFormValue('form[name="user"]', 'user[gender]', 'm');
    }
}