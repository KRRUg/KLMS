<?php

namespace App\DataFixtures;

use App\Entity\Seat;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class SeatmapFixture extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $seat1 = (new Seat())
            ->setType('seat')
            ->setOwner($this->getReference('gamer-1'))
            ->setPosX(10)->setPosY(15)
            ->setChairPosition('top')
            ->setSector('L')->setSeatNumber(1)
            ->setName('L-1');

        $seat2 = (new Seat())
            ->setType('seat')
            ->setOwner($this->getReference('gamer-2'))
            ->setPosX(15)->setPosY(15)
            ->setChairPosition('top')
            ->setSector('L')->setSeatNumber(2)
            ->setName('L-2');

        $seat3 = (new Seat())
            ->setType('seat')
            ->setOwner($this->getReference('gamer-3'))
            ->setPosX(20)->setPosY(15)
            ->setChairPosition('top')
            ->setSector('L')->setSeatNumber(3)
            ->setName('L-3');

        $manager->persist($seat1);
        $manager->persist($seat2);
        $manager->persist($seat3);
        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [UserFixtures::class];
    }
}