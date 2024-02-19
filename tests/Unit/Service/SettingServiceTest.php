<?php

namespace App\Tests\Unit\Service;

use App\Repository\SettingRepository;
use App\Service\SettingService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Vich\UploaderBundle\Templating\Helper\UploaderHelperInterface;

class SettingServiceTest extends TestCase
{
    public function testValidKeyExists()
    {
        $keys = SettingService::getKeys();
        foreach ($keys as $key) {
            $this->assertTrue(SettingService::validKey($key));
        }
    }

    public function testInvalidKey()
    {
        $key = 'invalid_key_that_does_not_exists';
        $this->assertFalse(SettingService::validKey($key));
        $this->assertEmpty(SettingService::getType($key));
    }

    public function testSettingDescription()
    {
        $key = 'site.title';
        $this->assertTrue(SettingService::validKey($key));
        $this->assertEquals('Titel der Seite', SettingService::getDescription($key));
        $this->assertEquals(SettingService::TB_TYPE_STRING, SettingService::getType($key));
    }

    public function testValidKeysExists()
    {
        $keys = SettingService::getKeys();
        $this->assertTrue(SettingService::validKeys($keys));
    }

    public function testValidKeys()
    {
        $keys = ['site.title', 'site.subtitle'];
        $this->assertTrue(SettingService::validKeys($keys));
    }

    public function testSomeValidKeys()
    {
        $keys = ['site.title', 'site.subtitle', 'some_invlid_key_that_does_not_exist'];
        $this->assertFalse(SettingService::validKeys($keys));
    }

    public function testKeyCase()
    {
        $key = 'SiTe.TiTLe';
        $this->assertTrue(SettingService::validKey($key));
        $this->assertEquals('Titel der Seite', SettingService::getDescription($key));
        $this->assertEquals(SettingService::TB_TYPE_STRING, SettingService::getType($key));
    }

    public function testKeysCase()
    {
        $keys = ['SiTe.titLe', 'sIte.SubTitle'];
        $this->assertTrue(SettingService::validKeys($keys));
    }
}