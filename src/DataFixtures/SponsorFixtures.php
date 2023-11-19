<?php

namespace App\DataFixtures;

use App\Entity\Sponsor;
use App\Entity\SponsorCategory;
use DateTime;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class SponsorFixtures extends Fixture implements DependentFixtureInterface
{
    private readonly Filesystem $filesystem;

    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    public function load(ObjectManager $manager): void
    {
        $manager->persist(SettingsFixture::createSetting('sponsor.enabled', true));
        $manager->persist(SettingsFixture::createSetting('sponsor.banner.show', true));

        $categories = [
            (new SponsorCategory())->setName('Super-Sponsor')->setPriority(3),
            (new SponsorCategory())->setName('Hyper-Sponsor')->setPriority(1),
            (new SponsorCategory())->setName('Mega-Sponsor')->setPriority(2),
        ];
        foreach ($categories as $category) {
            $manager->persist($category);
        }

        $src = __DIR__."/../../assets/images/logo.png";
        $src1 = sys_get_temp_dir() . '/logo1.png';
        $src2 = sys_get_temp_dir() . '/logo2.png';
        $this->filesystem->copy($src, $src1);
        $this->filesystem->copy($src, $src2);
        // no need to remove the files from temp, vich uploader moves them away

        $logos = [
            new UploadedFile(
                $src1,
                'logo.png',
                'image/png',
                null,
                true
            ),
            new UploadedFile(
                $src2,
                'logo.png',
                'image/png',
                null,
                true
            ),
        ];

        $sponsors = [
            (new Sponsor())
            ->setName("Cat 1")
            ->setCategory($categories[1])
            ->setText("Miau")
            ->setUrl(null)
            ->setLogoFile($logos[0])
            ->setCreated(new DateTime('2020-07-18 05:05'))
            ->setLastModified(new DateTime('2020-07-18 05:05'))
            ->setAuthorId(Uuid::fromInteger(strval(14)))
            ->setModifierId(Uuid::fromInteger(strval(14))),

            (new Sponsor())
            ->setName("Cat 2")
            ->setCategory($categories[1])
            ->setText("Miau Miau")
            ->setUrl(null)
            ->setLogoFile($logos[1])
            ->setCreated(new DateTime('2020-07-18 05:05'))
            ->setLastModified(new DateTime('2020-07-18 05:05'))
            ->setAuthorId(Uuid::fromInteger(strval(14)))
            ->setModifierId(Uuid::fromInteger(strval(14))),
        ];

        foreach ($sponsors as $sponsor) {
            $manager->persist($sponsor);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [UserFixtures::class, SettingsFixture::class];
    }
}
