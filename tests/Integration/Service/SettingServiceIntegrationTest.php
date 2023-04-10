<?php

namespace App\Tests\Integration\Service;

use App\Service\SettingService;
use App\Tests\Integration\DatabaseTestCase;

class SettingServiceIntegrationTest extends DatabaseTestCase
{
    public function testWriteAndReadSetting()
    {
        $settingService = self::getContainer()->get(SettingService::class);
        $key = 'site.title';
        $this->assertTrue(SettingService::validKey($key));
        $settingService->set($key, 'my fancy site');
        $this->assertEquals('my fancy site', $settingService->get($key));
        $this->assertEquals('my fancy site', $settingService->get($key, 'default value'));
        $this->assertTrue($settingService->remove($key));
        $this->assertFalse($settingService->isSet($key));
        $this->assertEquals('', $settingService->get($key));
        $this->assertEquals('default value', $settingService->get($key, 'default value'));
    }

    public function testIsSetAndDefault()
    {
        $settingService = self::getContainer()->get(SettingService::class);
        $key = 'site.title';
        $this->assertTrue(SettingService::validKey($key));
        $this->assertFalse($settingService->isSet($key));
        $this->assertEquals('', $settingService->get($key));
        $this->assertEquals('default value', $settingService->get($key, 'default value'));
        $this->assertFalse($settingService->remove($key));
    }

    public function testInvalidKey()
    {
        $settingService = self::getContainer()->get(SettingService::class);
        $key = 'invalid_key_that_for_sure_does_not_exists';
        $this->assertFalse(SettingService::validKey($key));
        $this->assertNull($settingService->get($key));
        $this->assertFalse($settingService->set($key, 'foo'));
        $this->assertNull($settingService->get($key));
        $this->assertNull($settingService->get($key, 'default'));
        $this->assertFalse($settingService->remove($key));
    }
}