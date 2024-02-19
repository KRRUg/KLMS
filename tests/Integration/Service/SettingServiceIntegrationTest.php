<?php

namespace App\Tests\Integration\Service;

use App\DataFixtures\SettingsFixture;
use App\Service\SettingService;
use App\Tests\Integration\DatabaseTestCase;

class SettingServiceIntegrationTest extends DatabaseTestCase
{
    public function testWriteAndReadSetting()
    {
        $this->databaseTool->loadFixtures([]);
        $settingService = self::getContainer()->get(SettingService::class);
        $key = 'site.title';
        $this->assertTrue(SettingService::validKey($key));
        $settingService->set($key, 'my fancy site');
        $this->assertEquals('my fancy site', $settingService->get($key));
        $this->assertEquals('my fancy site', $settingService->get($key, 'default value'));
        $this->assertTrue($settingService->clear($key));
        $this->assertFalse($settingService->isSet($key));
        $this->assertEquals('', $settingService->get($key));
        $this->assertEquals('default value', $settingService->get($key, 'default value'));
    }

    public function testIsSetAndDefault()
    {
        $this->databaseTool->loadFixtures([]);
        $settingService = self::getContainer()->get(SettingService::class);
        $key = 'site.title';
        $this->assertTrue(SettingService::validKey($key));
        $this->assertFalse($settingService->isSet($key));
        $this->assertEquals('', $settingService->get($key));
        $this->assertEquals('default value', $settingService->get($key, 'default value'));
        $this->assertFalse($settingService->clear($key));
    }

    public function testInvalidKey()
    {
        $this->databaseTool->loadFixtures([]);
        $settingService = self::getContainer()->get(SettingService::class);
        $key = 'invalid_key_that_for_sure_does_not_exists';
        $this->assertFalse(SettingService::validKey($key));
        $this->assertNull($settingService->get($key));
        $this->assertFalse($settingService->set($key, 'foo'));
        $this->assertNull($settingService->get($key));
        $this->assertNull($settingService->get($key, 'default'));
        $this->assertFalse($settingService->clear($key));
    }

    public function testClearKeys()
    {
        $this->databaseTool->loadFixtures([SettingsFixture::class]);
        $settingService = self::getContainer()->get(SettingService::class);
        $keys = ['site.title', 'site.subtitle'];
        $this->assertTrue(SettingService::validKeys($keys));
        $this->assertTrue($settingService->isSet($keys[0]));
        $this->assertTrue($settingService->isSet($keys[1]));
        $this->assertTrue($settingService->clearMultiple($keys));
        $this->assertFalse($settingService->isSet($keys[0]));
        $this->assertFalse($settingService->isSet($keys[1]));
    }

    public function testClearNotSetKeys()
    {
        $this->databaseTool->loadFixtures([SettingsFixture::class]);
        $settingService = self::getContainer()->get(SettingService::class);
        $keys = ['site.title', 'link.yt'];
        $this->assertTrue(SettingService::validKeys($keys));
        $this->assertTrue($settingService->isSet($keys[0]));
        $this->assertFalse($settingService->isSet($keys[1]));
        $this->assertFalse($settingService->clearMultiple($keys));
        $this->assertFalse($settingService->isSet($keys[0]));
        $this->assertFalse($settingService->isSet($keys[1]));
    }

    public function testClearInvalidKeys()
    {
        $this->databaseTool->loadFixtures([SettingsFixture::class]);
        $settingService = self::getContainer()->get(SettingService::class);
        $keys = ['site.title', 'invalid_key_that_does_not_exist'];
        $this->assertFalse(SettingService::validKeys($keys));
        $this->assertTrue($settingService->isSet($keys[0]));
        $this->assertFalse($settingService->isSet($keys[1]));
        $this->assertFalse($settingService->clearMultiple($keys));
        $this->assertFalse($settingService->isSet($keys[0]));
        $this->assertFalse($settingService->isSet($keys[1]));
    }
}