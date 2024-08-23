<?php

namespace App\DataFixtures;

use App\Entity\Seat;
use App\Entity\SeatKind;
use App\Entity\SeatOrientation;
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
            ->setType(SeatKind::SEAT)
            ->setOwner(Uuid::fromInteger(1))
            ->setPosX(50)->setPosY(15)
            ->setChairPosition(SeatOrientation::NORTH)
            ->setSector('L')->setSeatNumber(1)
            ->setName('L-1');

        $seat[] = (new Seat())
            ->setType(SeatKind::SEAT)
            ->setOwner(Uuid::fromInteger(2))
            ->setPosX(79)->setPosY(15)
            ->setChairPosition(SeatOrientation::NORTH)
            ->setSector('L')->setSeatNumber(2)
            ->setName('L-2');

        $seat[] = (new Seat())
            ->setType(SeatKind::SEAT)
            ->setOwner(Uuid::fromInteger(3))
            ->setPosX(140)->setPosY(15)
            ->setChairPosition(SeatOrientation::NORTH)
            ->setSector('L')->setSeatNumber(3)
            ->setName('L-3');

        $seat[] = (new Seat())
            ->setType(SeatKind::LOCKED)
            ->setPosX(50)->setPosY(50)
            ->setChairPosition(SeatOrientation::WEST)
            ->setSector('X')->setSeatNumber(1);

        $seat[] = (new Seat())
            ->setType(SeatKind::SEAT)
            ->setPosX(50)->setPosY(100)
            ->setChairPosition(SeatOrientation::WEST)
            ->setSector('X')->setSeatNumber(2);

        $seat[] = (new Seat())
            ->setType(SeatKind::SEAT)
            ->setPosX(50)->setPosY(150)
            ->setChairPosition(SeatOrientation::SOUTH)
            ->setSector('X')->setSeatNumber(3);

        $seat[] = (new Seat())
            ->setType(SeatKind::SEAT)
            ->setPosX(100)->setPosY(50)
            ->setChairPosition(SeatOrientation::EAST)
            ->setSector('Y')->setSeatNumber(1)
            ->setClanReservation(Uuid::fromInteger(0x3EA));

        $seat[] = (new Seat())
            ->setType(SeatKind::SEAT)
            ->setPosX(100)->setPosY(100)
            ->setChairPosition(SeatOrientation::EAST)
            ->setSector('Y')->setSeatNumber(2)
            ->setClanReservation(Uuid::fromInteger(0x3EA));

        $seat[] = (new Seat())
            ->setType(SeatKind::SEAT)
            ->setPosX(100)->setPosY(150)
            ->setChairPosition(SeatOrientation::SOUTH)
            ->setSector('Y')->setSeatNumber(3);

        $seat[] = (new Seat())
            ->setType(SeatKind::INFO)
            ->setPosX(50)->setPosY(200)
            ->setSector('Z')->setSeatNumber(1)
            ->setChairPosition(SeatOrientation::NORTH)
            ->setName('Gear');

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