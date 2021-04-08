<?php

namespace App\DataFixtures;

use App\Entity\EMailTemplate;
use App\Service\EMailService;
use App\Service\GroupService;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Ramsey\Uuid\Uuid;

class EmailFixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {
        $template = (new EMailTemplate())
            ->setName("KLMS Newsletter 1")
            ->setSubject("KLMS Newsletter")
            ->setBody("<p><h2>KLMS Newsletter</h2><p>Hallo {{name}},<br> das ist der ersten KLMS Newsletter.</p></p>")
            ->setDesignFile(EMailService::DESIGN_STANDARD)
            ->setRecipientGroup(GroupService::GROUP_NEWSLETTER)
            ->setAuthorId(Uuid::fromInteger(1))
            ->setModifierId(Uuid::fromInteger(1))
            ->setCreated(new \DateTime())
            ->setLastModified(new \DateTime());

        $manager->persist($template);
        $manager->flush();
    }
}
