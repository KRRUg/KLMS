<?php

namespace App\Tests\Integration;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

abstract class DatabaseTestCase extends KernelTestCase
{
    protected EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        self::bootKernel();

        $em = self::getContainer()->get('doctrine')->getManager();
        $this->entityManager = $em;

        $schemaTool = new SchemaTool($em);
        $metaData = $em->getMetadataFactory()->getAllMetadata();
        $schemaTool->updateSchema($metaData);

        parent::setUp();
    }

    protected function tearDown(): void
    {
        $schemaTool = new SchemaTool($this->entityManager);
        $schemaTool->dropDatabase();

        parent::tearDown();
    }
}