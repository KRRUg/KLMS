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
    }
}