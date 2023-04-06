<?php

namespace App\DataFixtures;

use App\Entity\Email;
use App\Service\EmailService;
use App\Service\GroupService;
use DateTime;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Ramsey\Uuid\Uuid;

class EmailFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $template = (new Email())
            ->setName('KLMS Newsletter 1')
            ->setSubject('KLMS Newsletter')
            ->setBody('<p><h2>KLMS Newsletter</h2><p>Hallo {{nickname}},<br> das ist der ersten KLMS Newsletter.</p></p>')
            ->setDesignFile(EmailService::DESIGN_STANDARD)
            ->setRecipientGroup(Uuid::fromString(GroupService::GROUP_NEWSLETTER))
            ->setAuthorId(Uuid::fromInteger(1))
            ->setModifierId(Uuid::fromInteger(1))
            ->setCreated(new DateTime())
            ->setLastModified(new DateTime());

        $manager->persist($template);
        $manager->flush();
    }
}
