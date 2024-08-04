<?php

namespace App\DataFixtures;

use App\Entity\Setting;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use joshtronic\LoremIpsum;

class SettingsFixture extends Fixture
{
    public static function createSetting(string $key, string $value): Setting
    {
        return (new Setting($key))->setText($value);
    }

    private function addSetting(ObjectManager $manager, string $key, string $value): void
    {
        $manager->persist(self::createSetting($key, $value));
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
        $this->addSetting($manager, 'lan.tourney.text', 'Unsere großartigen Turniere. Es gibt auch ganz <i>tolle</i> Preise, versprochen!');
        $this->addSetting($manager, 'lan.tourney.registration_open', true);

        $this->addSetting($manager, 'lan.signup.enabled', true);
        $this->addSetting($manager, 'lan.signup.price', 1337);
        $this->addSetting($manager, 'lan.signup.discount.price', 999);
        $this->addSetting($manager, 'lan.signup.discount.limit', 3);
        $this->addSetting($manager, 'lan.signup.payment_details', "<b>ACME Bank</b><br>IBAN: XX12 3456 7890 1337<br>BIC: ACME123");

        $manager->flush();
    }
}
