<?php

namespace App\Tests\Unit\Idm;

use App\Entity\Clan;
use App\Entity\User;
use App\Idm\UnitOfWork;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Nonstandard\Uuid;

class UnitOfWorkTest extends TestCase
{
    public function testUoWAttachObject()
    {
        $uow = new UnitOfWork();
        $uid = Uuid::fromInteger(1);
        $u1 = (new User())
            ->setId(1)
            ->setUuid($uid)
            ->setFirstname('Test')
            ->setSurname('User')
            ->setEmail('test@user.com');
        $uow->attach($u1);
        $this->assertFalse($uow->isDirty($u1));
        $this->assertEquals(UnitOfWork::STATE_MANAGED, $uow->getObjectState($u1));
        $u1->setCity('Town');
        $this->assertTrue($uow->isDirty($u1));
        $this->assertTrue($uow->isAttached($u1));
        $this->assertEquals(UnitOfWork::STATE_MODIFIED, $uow->getObjectState($u1));
        $this->assertEquals($u1, $uow->get(User::class, $uid->toString()));
    }

    public function testUoWPersistObject()
    {
        $uow = new UnitOfWork();
        $u1 = (new User())
            ->setFirstname('Test')
            ->setSurname('User')
            ->setEmail('test@user.com');
        $uow->persist($u1);
        $this->assertFalse($uow->isDirty($u1));
        $u1->setCity('Town');
        // false, as this is a new object
        $this->assertFalse($uow->isDirty($u1));
        $this->assertTrue($uow->isNew($u1));
        $this->assertEquals(UnitOfWork::STATE_CREATED, $uow->getObjectState($u1));
    }

    public function testUoWStates()
    {
        $uow = new UnitOfWork();
        $u1 = (new User())
            ->setId(1)
            ->setUuid(Uuid::fromInteger(1))
            ->setFirstname('Test')
            ->setSurname('User 1')
            ->setEmail('test@user.com');
        $u2 = (new User())
            ->setUuid(Uuid::fromInteger(2))
            ->setFirstname('Test')
            ->setSurname('User 2')
            ->setEmail('test1@user.com');
        $uow->attach($u1);
        $this->assertEquals(UnitOfWork::STATE_MANAGED, $uow->getObjectState($u1));
        $this->assertEquals(UnitOfWork::STATE_DETACHED, $uow->getObjectState($u2));
        $u1->setCity('Town');
        $this->assertEquals(UnitOfWork::STATE_MODIFIED, $uow->getObjectState($u1));
        $obj = $uow->get(User::class, $u1->getUuid()->toString());
        $this->assertEquals($u1, $obj);
        $uow->delete($u1);
        $uow->delete($u2);
        $this->assertEquals(UnitOfWork::STATE_DELETE, $uow->getObjectState($u1));
        $this->assertEquals(UnitOfWork::STATE_DETACHED, $uow->getObjectState($u2));
        $uow->persist($u2);
        $this->assertEquals(UnitOfWork::STATE_CREATED, $uow->getObjectState($u2));
    }

    public function testUoWPersists()
    {
        $uow = new UnitOfWork();
        $c1 = (new Clan())
            ->setName("Test Clan")
            ->setClantag('TC');
        $u1 = (new User())
            ->setFirstname('Test')
            ->setSurname('User')
            ->setEmail('test@user.com')
            ->setClans([$c1]);
        $u2 = (new User())
            ->setFirstname('Test')
            ->setSurname('User 2')
            ->setEmail('test2@user.com');
        $c1->setUsers([$u1, $u2]);

        $uow->persist($u1);

        $this->assertEquals(UnitOfWork::STATE_CREATED, $uow->getObjectState($u1));
        $this->assertEquals(UnitOfWork::STATE_CREATED, $uow->getObjectState($c1));
        $this->assertEquals(UnitOfWork::STATE_CREATED, $uow->getObjectState($u2));
        $this->assertCount(3, $uow->getObjects());
    }

    public function testUoWCreateAndDelete()
    {
        $uow = new UnitOfWork();
        $u1 = (new User())
            ->setFirstname('Test')
            ->setSurname('User')
            ->setEmail('test@user.com');

        $this->assertEquals(UnitOfWork::STATE_DETACHED, $uow->getObjectState($u1));
        $uow->persist($u1);
        $uow->delete($u1);
        $this->assertEquals(UnitOfWork::STATE_DETACHED, $uow->getObjectState($u1));
        $this->assertEmpty($uow->getObjects());
    }
}