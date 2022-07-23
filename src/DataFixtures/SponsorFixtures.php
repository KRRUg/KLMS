<?php

namespace App\DataFixtures;

use App\Entity\SponsorCategory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class SponsorFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $categories = [
            (new SponsorCategory())->setName("Super-Sponsor")->setPriority(3),
            (new SponsorCategory())->setName("Hyper-Sponsor")->setPriority(1),
            (new SponsorCategory())->setName("Mega-Sponsor")->setPriority(2),
        ];
        foreach ($categories as $category) {
            $manager->persist($category);
        }
        $manager->flush();
    }
}
