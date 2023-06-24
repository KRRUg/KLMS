<?php

namespace App\DataFixtures;

use App\Entity\Teamsite;
use App\Entity\TeamsiteCategory;
use App\Entity\TeamsiteEntry;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Ramsey\Uuid\Uuid;

class TeamsiteFixture extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $ts = (new Teamsite())
            ->setTitle('KLMS-Team')
            ->setDescription('Das Team hinter dem KLMS.')
            ->setAuthorId(Uuid::fromInteger(18))
            ->setModifierId(Uuid::fromInteger(18))
        ;

        $cat1 = (new TeamsiteCategory())
            ->setTitle('Backend')
            ->setDescription('Die Backend Developer')
            ->setHideName(false)
            ->setHideEmail(false)
            ->setOrd(1)
            ->addEntry((new TeamsiteEntry())
                ->setTitle('Chief Developer')
                ->setUserUuid(Uuid::fromInteger(18))
                ->setDescription('<i>Hacky</i> Hacker')
                ->setOrd(1)
            )
            ->addEntry((new TeamsiteEntry())
                ->setTitle('Senior Developer')
                ->setDescription('')
                ->setUserUuid(Uuid::fromInteger(7))
                ->setOrd(3)
            )
            ->addEntry((new TeamsiteEntry())
                ->setTitle('Senior Developer')
                ->setDescription('')
                ->setUserUuid(Uuid::fromInteger(18))
                ->setOrd(2)
            );

        $cat2 = (new TeamsiteCategory())
            ->setTitle('Frontend')
            ->setDescription('Die Frontend Developer')
            ->setHideName(false)
            ->setHideEmail(true)
            ->setOrd(2)
            ->addEntry((new TeamsiteEntry())
                ->setUserUuid(Uuid::fromInteger(13))
                ->setTitle('JS Developer')
                ->setDescription('I ❤ JS')
                ->setOrd(1)
            );

        $cat3 = (new TeamsiteCategory())
            ->setTitle('Q&A Team')
            ->setDescription('')
            ->setHideName(true)
            ->setHideEmail(true)
            ->setOrd(3);

        $ts->addCategory($cat1);
        $ts->addCategory($cat2);
        $ts->addCategory($cat3);

        $this->setReference('teamsite-0', $ts);
        $manager->persist($ts);
        $manager->flush();
    }
}
