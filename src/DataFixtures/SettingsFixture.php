<?php

namespace App\DataFixtures;

use App\Entity\Setting;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use joshtronic\LoremIpsum;

class SettingsFixture extends Fixture
{
    private function addSetting(ObjectManager $manager, string $key, string $value): void
    {
        $setting = new Setting($key);
        $setting->setText($value);
        $manager->persist($setting);
    }

    public function load(ObjectManager $manager): void
    {
        $lipsum = new LoremIpsum();

        $this->addSetting($manager, 'site.organisation', 'KLMS Team');
        $this->addSetting($manager, 'site.title', 'KRRU Lan Management System');
        $this->addSetting($manager, 'site.subtitle', 'System zur Organisation von professionellen LAN-Partys');
        $this->addSetting($manager, 'site.about', $lipsum->words(20));
        $this->addSetting($manager, 'link.steam', 'https://store.steampowered.com/');
        $this->addSetting($manager, 'link.discord', 'https://discord.com/');

        $this->addSetting($manager, 'email.register.subject', 'Registrierung');
        $this->addSetting($manager, 'email.register.text', "<h2>{$lipsum->words()}</h2><p>{$lipsum->paragraphs(2)}</p><h2>{$lipsum->words(2)}</h2><p>{$lipsum->paragraphs(3)}}</p>");

        $this->addSetting($manager, 'lan.seatmap.enabled', true);

        $this->addSetting($manager, 'lan.tourney.enabled', true);

        $manager->flush();
    }
}
