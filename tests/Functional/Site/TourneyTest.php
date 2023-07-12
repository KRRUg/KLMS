<?php

namespace App\Tests\Functional\Site;

use App\DataFixtures\TourneyFixture;
use App\DataFixtures\TourneyFixtureGames;
use App\DataFixtures\UserFixtures;
use App\Tests\Functional\DatabaseWebTestCase;
use Generator;

class TourneyTest extends DatabaseWebTestCase
{
    public function testTourneyWithoutLogin()
    {
        $this->databaseTool->loadFixtures([TourneyFixture::class, UserFixtures::class]);
        $crawler = $this->client->request('GET', '/tourney');
        $this->assertSelectorNotExists('.tourney.registered');
        $tourneys = $crawler->filter('.tourney');
        $this->assertEquals(4, $tourneys->count());
        $this->assertStringContainsString('Chess 1v1', $tourneys->getNode(0)->textContent);
        $this->assertStringContainsString('Poker', $tourneys->getNode(1)->textContent);
        $this->assertStringContainsString('Chess 2v2', $tourneys->getNode(2)->textContent);
    }

    public function testTourneyListWithLogin()
    {
        $this->databaseTool->loadFixtures([TourneyFixture::class, UserFixtures::class]);

        $this->login('user7@localhost.local');
        $crawler = $this->client->request('GET', '/tourney');
        $this->assertEquals(4, $crawler->filter('.tourney')->count());
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
        $this->assertSelectorExists('.alert');
    }

    public function testTourneyRegistrationJoinTeamSuccess()
    {
        $this->databaseTool->loadFixtures([TourneyFixture::class, UserFixtures::class]);

        $this->login('user7@localhost.local');
        $crawler = $this->client->request('GET', '/tourney');
        $tourney = $crawler->filter('#tourney-2');
        $this->assertStringContainsString('Chess 2v2', $tourney->filter('button')->innerText());
        $this->assertSelectorNotExists('#tourney-2.registered');

        $button = $crawler->selectButton('Beitreten');
        $this->assertNotEmpty($button);
        $form = $button->form();

        $values = $form[$form->getName().'[team]']->availableOptionValues();
        $form[$form->getName().'[team]']->select($values[1]);
        $crawler = $this->client->submit($form);
        $this->assertResponseStatusCodeSame(200);
        $this->assertSelectorExists('#tourney-2.registered');
    }

    public function testTourneyRegistrationJoinTeamFull()
    {
        $this->databaseTool->loadFixtures([TourneyFixture::class, UserFixtures::class]);

        $this->login('user7@localhost.local');
        $crawler = $this->client->request('GET', '/tourney');
        $tourney = $crawler->filter('#tourney-2');
        $this->assertStringContainsString('Chess 2v2', $tourney->filter('button')->innerText());
        $this->assertSelectorNotExists('#tourney-2.registered');

        $button = $crawler->selectButton('Beitreten');
        $this->assertNotEmpty($button);
        $form = $button->form()->disableValidation();
        $values = $form[$form->getName().'[team]']->availableOptionValues();
        $form[$form->getName().'[team]']->select($values[0]);
        $crawler = $this->client->submit($form);
        $this->assertResponseStatusCodeSame(200);
        $this->assertSelectorNotExists('#tourney-2.registered');
        $this->assertSelectorExists('.alert');
    }

    public function testTourneyRegistrationJoinTeamNotExists()
    {
        $this->databaseTool->loadFixtures([TourneyFixture::class, UserFixtures::class]);

        $this->login('user7@localhost.local');
        $crawler = $this->client->request('GET', '/tourney');
        $tourney = $crawler->filter('#tourney-2');
        $this->assertStringContainsString('Chess 2v2', $tourney->filter('button')->innerText());
        $this->assertSelectorNotExists('#tourney-2.registered');

        $button = $crawler->selectButton('Beitreten');
        $this->assertNotEmpty($button);
        $form = $button->form()->disableValidation();
        $form[$form->getName().'[team]']->setValue(123);
        $crawler = $this->client->submit($form);
        $this->assertResponseStatusCodeSame(200);
        $this->assertSelectorNotExists('#tourney-2.registered');
    }

    public function testTourneyRegistrationStartedTourney()
    {
        $this->databaseTool->loadFixtures([TourneyFixture::class, UserFixtures::class]);

        $this->login('user14@localhost.local');
        $crawler = $this->client->request('GET', '/tourney');
        $this->assertSelectorExists('#tourney-1');

        // "borrow" form from 2nd tournament
        $form = $crawler->filter('form')->first()->form();
        $form[$form->getName().'[id]'] = 1;
        $crawler = $this->client->submit($form);
        $this->assertSelectorNotExists('#tourney-1.registered');
        $this->assertResponseStatusCodeSame(200);
        $this->assertSelectorExists('.alert');
    }

    public function testTourneyRegistrationAlreadyRegistered()
    {
        $this->databaseTool->loadFixtures([TourneyFixture::class, UserFixtures::class]);

        $this->login('user14@localhost.local');
        $crawler = $this->client->request('GET', '/tourney');
        $this->assertSelectorNotExists('#tourney-3.registered');

        $form_node = $crawler->filter('#tourney-3')->filter('form');
        $form = $form_node->form();
        $this->client->submit($form);
        $this->assertResponseStatusCodeSame(200);
        $this->assertSelectorExists('#tourney-3.registered');

        $form_node = $crawler->filter('#tourney-3')->filter('form');
        $form = $form_node->form();
        $this->client->submit($form);
        $this->assertResponseStatusCodeSame(200);
        $this->assertSelectorExists('#tourney-3.registered');
        $this->assertSelectorExists('.alert');
    }

    public function testTourneyRegistrationInsufficientToken()
    {
        $this->databaseTool->loadFixtures([TourneyFixture::class, UserFixtures::class]);

        $this->login('user7@localhost.local');
        $crawler = $this->client->request('GET', '/tourney');
        $this->assertSelectorExists('#tourney-1.registered');
        $this->assertSelectorNotExists('#tourney-2.registered');
        $this->assertSelectorNotExists('#tourney-3.registered');

        $form_node = $crawler->filter('#tourney-3')->filter('form');
        $form = $form_node->form();
        $this->client->submit($form);
        $this->assertResponseStatusCodeSame(200);
        $this->assertSelectorExists('#tourney-3.registered');

        $node = $crawler->filter('#tourney-2')->selectButton('Erstellen');
        $form = $node->form();
        $form[$form->getName().'[name]'] = 'new team';
        $this->client->submit($form);
        $this->assertResponseStatusCodeSame(200);
        $this->assertSelectorNotExists('#tourney-2.registered');
        $this->assertSelectorExists('.alert');
    }

    public function testTourneyUnregister()
    {
        $this->databaseTool->loadFixtures([TourneyFixture::class, UserFixtures::class]);

        $this->login('user9@localhost.local');

        $crawler = $this->client->request('GET', '/tourney');
        $this->assertSelectorExists('#tourney-3.registered');

        $button = $crawler->selectButton('Abmelden');
        $this->assertNotEmpty($button);
        $form = $button->form();

        $this->client->submit($form);

        $this->assertSelectorNotExists('#tourney-3.registered');
    }

    public function testTourneyUnregisterTeam()
    {
        $this->databaseTool->loadFixtures([TourneyFixture::class, UserFixtures::class]);

        $this->login('user2@localhost.local');

        $crawler = $this->client->request('GET', '/tourney');
        $this->assertSelectorExists('#tourney-2.registered');

        $button = $crawler->selectButton('Abmelden');
        $this->assertNotEmpty($button);
        $form = $button->form();
        $this->client->submit($form);
        $this->assertSelectorNotExists('#tourney-2.registered');
    }

    public function testTourneyAcceptUser()
    {
        $this->databaseTool->loadFixtures([TourneyFixture::class, UserFixtures::class]);

        $this->login('user1@localhost.local');

        $crawler = $this->client->request('GET', '/tourney');
        $this->assertSelectorExists('#tourney-4.registered');

        $form_nodes = $crawler->filter('form[name=form_confirm]');
        $this->assertCount(3, $form_nodes);
        $form_node = $form_nodes->first();
        $accept = $form_node->filter('#form_confirm_accept');
        $this->assertNotEmpty($accept);
        $this->assertStringContainsString('User 5', $accept->attr('title'));
        $form = $accept->form();
        $this->client->submit($form);
        $this->assertSelectorTextContains('#tourney-4 .card', 'User 5');

        $this->logout();
        $this->login('user5@localhost.local');
        $this->client->request('GET', '/tourney');
        $this->assertSelectorExists('#tourney-4.registered');
        $this->assertSelectorTextNotContains('#tourney-4.registered', 'Warten');
    }

    public function testTourneyDeclineUser()
    {
        $this->databaseTool->loadFixtures([TourneyFixture::class, UserFixtures::class]);

        $this->login('user1@localhost.local');

        $crawler = $this->client->request('GET', '/tourney');
        $this->assertSelectorExists('#tourney-4.registered');

        $form_nodes = $crawler->filter('form[name=form_confirm]');
        $this->assertCount(3, $form_nodes);
        $form_node = $form_nodes->first();
        $decline = $form_node->filter('#form_confirm_decline');
        $this->assertNotEmpty($decline);
        $this->assertStringContainsString('User 5', $decline->attr('title'));
        $form = $decline->form();
        $this->client->submit($form);
        $this->assertSelectorTextNotContains('#tourney-4 .card', 'User 5');

        $this->logout();
        $this->login('user5@localhost.local');
        $this->client->request('GET', '/tourney');
        $this->assertSelectorNotExists('#tourney-4.registered');
    }

    public function testTourneyUserWaiting()
    {
        $this->databaseTool->loadFixtures([TourneyFixture::class, UserFixtures::class]);

        $this->login('user5@localhost.local');

        $this->client->request('GET', '/tourney');
        $this->assertSelectorExists('#tourney-4.registered');
        $this->assertSelectorTextContains('#tourney-4.registered', 'Warten');
    }

    public function testTourneyUserEliminated()
    {
        $this->databaseTool->loadFixtures([TourneyFixture::class, UserFixtures::class]);

        $this->login('user5@localhost.local');

        $this->client->request('GET', '/tourney');
        $this->assertSelectorExists('#tourney-1.registered');
        $this->assertSelectorTextContains('#tourney-1.registered', 'Du bist nicht mehr im Rennen');
    }

    public function testTourneyNoOptionsForNonLogin()
    {
        $this->databaseTool->loadFixtures([TourneyFixture::class, UserFixtures::class]);
        $crawler = $this->client->request('GET', '/tourney');
        $this->assertSelectorNotExists('.registered');
        $buttons = $crawler->filter('#tourney-list button');
        foreach ($buttons as $button) {
            $this->assertStringNotContainsStringIgnoringCase('Anmelden', $button->textContent);
            $this->assertStringNotContainsStringIgnoringCase('Ergebnis eintragen', $button->textContent);
        }
        $this->assertStringNotContainsStringIgnoringCase('ausgeschieden', $crawler->filter('#tourney-list')->innerText());
    }

    public function testUserNotOnLanMayNotParticipate()
    {
        $this->databaseTool->loadFixtures([TourneyFixture::class, UserFixtures::class]);
        $this->login('user0@localhost.local');
        $crawler = $this->client->request('GET', '/tourney');
        $this->assertSelectorNotExists('.registered');
        $buttons = $crawler->filter('#tourney-list button');
        foreach ($buttons as $button) {
            $this->assertStringNotContainsStringIgnoringCase('Anmelden', $button->textContent);
            $this->assertStringNotContainsStringIgnoringCase('Ergebnis eintragen', $button->textContent);
        }
        $this->assertStringNotContainsStringIgnoringCase('ausgeschieden', $crawler->filter('#tourney-list')->innerText());
    }

    private static function provideUsers(): Generator
    {
        $fixtures = [TourneyFixture::class, TourneyFixtureGames::class];
        $users = ['', 'user6@localhost.local'];
        foreach ($fixtures as $fix) {
            foreach ($users as $user) {
                yield [$fix, $user];
            }
        }
    }

    /**
     * @dataProvider provideUsers
     */
    public function testTourneyResult(string $fixture, string $user)
    {
        $this->databaseTool->loadFixtures([$fixture]);

        if (!empty($user))
            $this->login($user);

        $crawler = $this->client->request('GET', '/tourney');
        if ($fixture === TourneyFixtureGames::class) {
            $results = $crawler->filter('#tourney-1 ul.results');
            $this->assertNotEmpty($results);
            $resultItems = $results->filter('li');
            $this->assertCount(4, $resultItems);
            $this->assertStringContainsString('User 6', $resultItems->getNode(0)->textContent);
            $this->assertStringContainsString('User 1', $resultItems->getNode(1)->textContent);
            $this->assertStringContainsString('User 3', $resultItems->getNode(2)->textContent);
            $this->assertStringContainsString('User 8', $resultItems->getNode(3)->textContent);
        } else {
            $this->assertSelectorNotExists('#tourney-1 .results');
        }
    }

    /**
     * @dataProvider provideUsers
     */
    public function testTourneyTree(string $fixture, string $user)
    {
        $this->databaseTool->loadFixtures([$fixture]);

        if (!empty($user))
            $this->login($user);

        $crawler = $this->client->request('GET', '/tourney/1');
        $tourney = $crawler->filter('.tournament');
        $this->assertNotEmpty($tourney);
        $games = $tourney->filter('.match');
        $this->assertCount(7, $games);

        if (empty($user)) {
            $this->assertSelectorNotExists('.tournament .team-own');
        } else {
            $this->assertSelectorExists('.tournament .team-own');
        }

        $teams = $tourney->filter('.team');
        $this->assertCount(14, $teams);
        if ($fixture === TourneyFixtureGames::class) {
            foreach ($teams as $item) {
                $this->assertStringContainsString('User', $item->textContent);
            }
        } else {
            $this->assertSelectorExists('.team:not(.team-name)');
        }
    }
}