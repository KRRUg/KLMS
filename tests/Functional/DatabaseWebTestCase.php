<?php

namespace App\Tests\Functional;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

abstract class DatabaseWebTestCase extends WebTestCase
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

        self::ensureKernelShutdown();
    }

    protected function tearDown(): void
    {
        $schemaTool = new SchemaTool($this->entityManager);
        $schemaTool->dropDatabase();

        parent::tearDown();
    }
}