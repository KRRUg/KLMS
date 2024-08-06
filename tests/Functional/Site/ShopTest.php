<?php

namespace App\Tests\Functional\Site;

use App\DataFixtures\ShopFixture;
use App\DataFixtures\UserFixtures;
use App\Tests\Functional\DatabaseWebTestCase;

class ShopTest extends DatabaseWebTestCase
{
    private function provideCodes(): array
    {
        return [
            ["", false],
            ["INVALIC_FORMAT", false],
            ["CODE1-KRRUG-BBBBB", true],  // valid code
            ["CODE1-KRRUG-XXXXX", false], // invalid code
            ["CODE1-KRRUG-AAAAA", false], // valid but used code
        ];
    }

    /**
     * @dataProvider provideCodes
     */
    public function testCodeCheck(string $code, bool $expected)
    {
        $this->databaseTool->loadFixtures([ShopFixture::class, UserFixtures::class]);

        $this->login('user3@localhost.local');
        $this->client->request('GET', '/shop/check', empty($code) ? [] : ['code' => $code]);
        $this->assertResponseStatusCodeSame(200);
        $json = $this->client->getResponse()->getContent();
        $this->assertJson($json);
        $result = json_decode($json, associative: true, depth: 2);
        $this->assertArrayHasKey('result', $result);
        $this->assertIsBool($result['result']);
        $this->assertEquals($expected, $result['result']);
    }
}