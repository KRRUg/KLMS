<?php

namespace App\Tests\Functional\Site;

use App\DataFixtures\TourneyFixture;
use App\DataFixtures\UserFixtures;
use App\Tests\Functional\DatabaseWebTestCase;

class TourneyTest extends DatabaseWebTestCase
{
    public function testTourneyWithoutLogin()
    {
        $this->databaseTool->loadFixtures([TourneyFixture::class, UserFixtures::class]);
        $crawler = $this->client->request('GET', '/tourney');
        $this->assertSelectorNotExists('.tourney.registered');
        $tourneys = $crawler->filter('.tourney');
        $this->assertEquals(3, $tourneys->count());
        $this->assertStringContainsString('Chess 1v1', $tourneys->getNode(0)->textContent);
        $this->assertStringContainsString('Poker', $tourneys->getNode(1)->textContent);
        $this->assertStringContainsString('Chess 2v2', $tourneys->getNode(2)->textContent);
    }

    public function testTourneyListWithLogin()
    {
        $this->databaseTool->loadFixtures([TourneyFixture::class, UserFixtures::class]);

        $this->login('user7@localhost.local');
        $crawler = $this->client->request('GET', '/tourney');
        $this->assertEquals(3, $crawler->filter('.tourney')->count());
        $this->assertEquals(1, $crawler->filter('.tourney.registered')->count());
        $this->logout();
        $this->login('user2@localhost.local');
        $crawler = $this->client->request('GET', '/tourney');
        $this->assertEquals(2, $crawler->filter('.tourney.registered')->count());
    }

    public function testTourneyRegistrationSinglePlayer()
    {
        $this->databaseTool->loadFixtures([TourneyFixture::class, UserFixtures::class]);

        $this->login('user7@localhost.local');
        $crawler = $this->client->request('GET', '/tourney');
        $tourney = $crawler->filter('#tourney-3');
        $this->assertStringContainsString('Poker', $tourney->filter('button')->innerText());
        $this->assertSelectorNotExists('#tourney-3.registered');

        $form_node = $tourney->filter('form');
        $form = $form_node->form();
        $this->assertEquals('Teilnehmen', $form_node->filter('button[type=submit]')->innerText());
        $this->assertEquals('POST', $form->getMethod());

        $crawler = $this->client->submit($form);
        $this->assertSelectorExists('#tourney-3.registered');
    }

    public function testTourneyRegistrationIncorrectId()
    {
        $this->databaseTool->loadFixtures([TourneyFixture::class, UserFixtures::class]);

        $this->login('user7@localhost.local');
        $crawler = $this->client->request('GET', '/tourney');
        $tourney = $crawler->filter('#tourney-3');
        $this->assertStringContainsString('Poker', $tourney->filter('button')->innerText());
        $this->assertSelectorNotExists('#tourney-3.registered');

        $form_node = $tourney->filter('form');
        $form = $form_node->form()->disableValidation();
        $form[$form->getName().'[id]']->setValue('no_number');
        $crawler = $this->client->submit($form);
        $this->assertResponseStatusCodeSame(400);
    }

    public function testTourneyRegistrationMissingId()
    {
        $this->databaseTool->loadFixtures([TourneyFixture::class, UserFixtures::class]);

        $this->login('user7@localhost.local');
        $crawler = $this->client->request('GET', '/tourney');
        $tourney = $crawler->filter('#tourney-3');
        $this->assertStringContainsString('Poker', $tourney->filter('button')->innerText());
        $this->assertSelectorNotExists('#tourney-3.registered');

        $form_node = $tourney->filter('form');
        $form = $form_node->form()->disableValidation();
        $form->remove($form->getName().'[id]');
        $crawler = $this->client->submit($form);
        $this->assertResponseStatusCodeSame(400);
    }

    public function testTourneyRegistrationFormsTeams()
    {
        $this->databaseTool->loadFixtures([TourneyFixture::class, UserFixtures::class]);

        $this->login('user7@localhost.local');
        $crawler = $this->client->request('GET', '/tourney');
        $tourney = $crawler->filter('#tourney-2');
        $this->assertStringContainsString('Chess 2v2', $tourney->filter('button')->innerText());
        $this->assertSelectorNotExists('#tourney-2.registered');

        $form_node = $tourney->filter('form');
        $this->assertEquals(2, $form_node->count());
        $this->assertNotEmpty($form_node->filter('form > select'));
        $this->assertNotEmpty($form_node->filter('form > input[type=text]'));
        $this->assertResponseStatusCodeSame(200);
    }

    public function testTourneyRegistrationNewTeamSuccess()
    {
        $this->databaseTool->loadFixtures([TourneyFixture::class, UserFixtures::class]);

        $this->login('user7@localhost.local');
        $crawler = $this->client->request('GET', '/tourney');
        $tourney = $crawler->filter('#tourney-2');
        $this->assertStringContainsString('Chess 2v2', $tourney->filter('button')->innerText());
        $this->assertSelectorNotExists('#tourney-2.registered');

        $button = $crawler->selectButton('Erstellen');
        $this->assertNotEmpty($button);
        $form = $button->form();
        $form[$form->getName().'[name]'] = 'fup';
        $crawler = $this->client->submit($form);
        $this->assertResponseStatusCodeSame(200);
        $this->assertSelectorExists('#tourney-2.registered');
    }

    public function testTourneyRegistrationNewTeamEmptyName()
    {
        $this->databaseTool->loadFixtures([TourneyFixture::class, UserFixtures::class]);

        $this->login('user7@localhost.local');
        $crawler = $this->client->request('GET', '/tourney');
        $tourney = $crawler->filter('#tourney-2');
        $this->assertStringContainsString('Chess 2v2', $tourney->filter('button')->innerText());
        $this->assertSelectorNotExists('#tourney-2.registered');

        $button = $crawler->selectButton('Erstellen');
        $this->assertNotEmpty($button);
        $form = $button->form()->disableValidation();
        $form[$form->getName().'[name]'] = '';
        $crawler = $this->client->submit($form);
        $this->assertResponseStatusCodeSame(200);
        $this->assertSelectorNotExists('#tourney-2.registered');
    }

    public function testTourneyRegistrationNewTeamNoName()
    {
        $this->databaseTool->loadFixtures([TourneyFixture::class, UserFixtures::class]);

        $this->login('user7@localhost.local');
        $crawler = $this->client->request('GET', '/tourney');
        $tourney = $crawler->filter('#tourney-2');
        $this->assertStringContainsString('Chess 2v2', $tourney->filter('button')->innerText());
        $this->assertSelectorNotExists('#tourney-2.registered');

        $button = $crawler->selectButton('Erstellen');
        $this->assertNotEmpty($button);
        $form = $button->form()->disableValidation();
        $form->remove($form->getName().'[name]');
        $crawler = $this->client->submit($form);
        $this->assertResponseStatusCodeSame(200);
        $this->assertSelectorNotExists('#tourney-2.registered');
    }

    public function testTourneyRegistrationNewTeamNameExists()
    {
        $this->databaseTool->loadFixtures([TourneyFixture::class, UserFixtures::class]);

        $this->login('user7@localhost.local');
        $crawler = $this->client->request('GET', '/tourney');
        $tourney = $crawler->filter('#tourney-2');
        $this->assertStringContainsString('Chess 2v2', $tourney->filter('button')->innerText());
        $this->assertSelectorNotExists('#tourney-2.registered');

        $button = $crawler->selectButton('Erstellen');
        $this->assertNotEmpty($button);
        $form = $button->form();
        $form[$form->getName().'[name]'] = 'Pro Team 2';
        $crawler = $this->client->submit($form);
        $this->assertResponseStatusCodeSame(200);
        $this->assertSelectorNotExists('#tourney-2.registered');
    }

    public function testTourneyTree()
    {
        $this->databaseTool->loadFixtures([TourneyFixture::class, UserFixtures::class]);

        $crawler = $this->client->request('GET', '/tourney/1');
    }
}