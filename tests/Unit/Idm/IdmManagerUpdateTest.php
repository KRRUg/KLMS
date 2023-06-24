<?php

namespace App\Tests\Unit\Idm;

use App\Entity\User;
use App\Idm\IdmManager;
use App\Tests\IdmServerMock;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpClient\MockHttpClient;

class IdmManagerUpdateTest extends TestCase
{
    private function createManager(bool $answerWithDetails = true): array
    {
        $mock = new IdmServerMock($answerWithDetails);
        $mockClient = new MockHttpClient($mock);
        $mockLogger = $this->createMock(LoggerInterface::class);
        $manager = new IdmManager($mockClient, $mockLogger);
        return array($mock, $manager);
    }

    public function testUpdateUserRequests()
    {
        /**
         * @var IdmManager $manager
         * @var IdmServerMock $mock
         * @var User $user
         */
        list($mock, $manager) = $this->createManager();
        $user = $manager->request(User::class, Uuid::fromInteger(strval(1)));

        $this->assertEquals(1, $mock->countRequests());
        $manager->flush();
        $this->assertEquals(1, $mock->countRequests());
        $user->setFirstname('Test');
        $manager->flush();
        $this->assertEquals(2, $mock->countRequests());
        $this->assertEquals(1, $mock->countRequests('PATCH'));
    }

    public function testCreate()
    {
        /**
         * @var IdmManager $manager
         * @var IdmServerMock $mock
         * @var User $user
         */
        list($mock, $manager) = $this->createManager();

        $u1 = (new User())
            ->setFirstname('Test')
            ->setSurname('User')
            ->setEmail('test@user.com');

        $manager->persist($u1);
        $manager->flush();
        $this->assertEquals(1, $mock->countRequests('POST'));
        $this->assertEquals(1, $mock->countRequests());
        $this->assertNotEmpty($u1->getUuid());
    }

    public function testCreateAndDelete()
    {
        /**
         * @var IdmManager $manager
         * @var IdmServerMock $mock
         * @var User $user
         */
        list($mock, $manager) = $this->createManager();

        $u1 = (new User())
            ->setFirstname('Test')
            ->setSurname('User')
            ->setEmail('test@user.com');

        $manager->persist($u1);
        $manager->remove($u1);
        $manager->flush();
        $this->assertEquals(0, $mock->countRequests());
    }
}