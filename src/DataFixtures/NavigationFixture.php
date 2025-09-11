<?php

namespace App\DataFixtures;

use App\Entity\Content;
use App\Entity\Navigation;
use App\Entity\NavigationNodeContent;
use App\Entity\NavigationNodeEmpty;
use App\Entity\NavigationNodeGeneric;
use App\Entity\NavigationNodeRoot;
use App\Entity\NavigationNodeTeamsite;
use App\Entity\Setting;
use App\Entity\Teamsite;
use App\Entity\TeamsiteCategory;
use App\Entity\TeamsiteEntry;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use joshtronic\LoremIpsum;
use Ramsey\Uuid\Uuid;

class NavigationFixture extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $nav = new Navigation();
        $nav->setName('main_menu');
        $nav->setMaxDepth(2);
        $nav->addNode((new NavigationNodeRoot())->setPos(1, 26));
        $nav->addNode((new NavigationNodeGeneric())->setName('Home')->setPos(2, 3));
        $nav->addNode((new NavigationNodeEmpty())->setName('Lan Party')->setPos(4, 15));
        $nav->addNode((new NavigationNodeContent($this->getReference('content-0', Content::class)))->setName('Facts')->setPos(5, 6));
        $nav->addNode((new NavigationNodeContent($this->getReference('content-4', Content::class)))->setName('Netzwerk')->setPos(7, 8));
        $nav->addNode((new NavigationNodeContent($this->getReference('content-3', Content::class)))->setName('Catering')->setPos(9, 10));
        $nav->addNode((new NavigationNodeContent($this->getReference('content-1', Content::class)))->setName('FAQ')->setPos(11, 12));
        $nav->addNode((new NavigationNodeContent($this->getReference('content-2', Content::class)))->setName('Location')->setPos(13, 14));
        $nav->addNode((new NavigationNodeTeamsite($this->getReference('teamsite-0', Teamsite::class)))->setName('Team')->setPos(16, 17));
        $nav->addNode((new NavigationNodeGeneric('/seatmap'))->setName('Sitzplan')->setPos(18, 19));
        $nav->addNode((new NavigationNodeGeneric('/tourney'))->setName('Turniere')->setPos(20, 21));
        $nav->addNode((new NavigationNodeGeneric('/sponsor'))->setName('Sponsoren')->setPos(22, 23));
        $nav->addNode((new NavigationNodeGeneric('/shop/checkout'))->setName('Shop')->setPos(24, 25));
        $manager->persist($nav);

        $footer = new Navigation();
        $footer->setName('footer');
        $footer->setMaxDepth(1);
        $footer->addNode((new NavigationNodeRoot())->setName('Footer')->setPos(1, 8));
        $footer->addNode((new NavigationNodeGeneric())->setName('AGB')->setPath('/')->setPos(2, 3));
        $footer->addNode((new NavigationNodeGeneric())->setName('Impressum')->setPath('/')->setPos(4, 5));
        $footer->addNode((new NavigationNodeGeneric())->setName('Datenschutz')->setPath('/')->setPos(6, 7));
        $manager->persist($footer);

        $manager->flush();
        $manager->refresh($nav);
        $manager->refresh($footer);
        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            ContentFixture::class,
            TeamsiteFixture::class
        ];
    }
}
