<?php

namespace App\DataFixtures;

use App\Entity\Seat;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Ramsey\Uuid\Uuid;

class SeatmapFixture extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $seat = [];

        $seat[] = (new Seat())
            ->setType('seat')
            ->setOwner(Uuid::fromInteger(1))
            ->setPosX(50)->setPosY(15)
            ->setChairPosition('top')
            ->setSector('L')->setSeatNumber(1)
            ->setName('L-1');

        $seat[] = (new Seat())
            ->setType('seat')
            ->setOwner(Uuid::fromInteger(2))
            ->setPosX(79)->setPosY(15)
            ->setChairPosition('top')
            ->setSector('L')->setSeatNumber(2)
            ->setName('L-2');

        $seat[] = (new Seat())
            ->setType('seat')
            ->setOwner(Uuid::fromInteger(3))
            ->setPosX(140)->setPosY(15)
            ->setChairPosition('top')
            ->setSector('L')->setSeatNumber(3)
            ->setName('L-3');

        $seat[] = (new Seat())
            ->setType('seat')
            ->setPosX(50)->setPosY(50)
            ->setChairPosition('right')
            ->setSector('X')->setSeatNumber(1);

        $seat[] = (new Seat())
            ->setType('seat')
            ->setPosX(50)->setPosY(100)
            ->setChairPosition('left')
            ->setSector('X')->setSeatNumber(2);

        $seat[] = (new Seat())
            ->setType('seat')
            ->setPosX(50)->setPosY(150)
            ->setChairPosition('bottom')
            ->setSector('X')->setSeatNumber(3);

        for ($i = 0; $i < count($seat); $i++) {
            $manager->persist($seat[$i]);
            $this->setReference("seat-{$i}", $seat[$i]);
        }
        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [UserFixtures::class, ShopFixture::class, SettingsFixture::class];
    }
}