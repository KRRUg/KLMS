<?php

namespace App\DataFixtures;

use App\Entity\Media;
use DateTime;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Ramsey\Uuid\Uuid;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class MediaFixtures extends Fixture
{
    private readonly Filesystem $filesystem;

    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    public function load(ObjectManager $manager)
    {
        $src = __DIR__."/../../assets/images/logo.png";
        $src1 = sys_get_temp_dir() . '/logo1.png';
        $this->filesystem->copy($src, $src1);
        // no need to remove the files from temp, vich uploader moves them away

        $logos = [
            new UploadedFile(
                $src1,
                'logo.png',
                'image/png',
                null,
                true
            ),
        ];

        $media = (new Media())
            ->setMediaFile($logos[0])
            ->setCreated(new DateTime('2020-07-18 05:05'))
            ->setLastModified(new DateTime('2020-07-18 05:05'))
            ->setAuthorId(Uuid::fromInteger(strval(14)))
            ->setModifierId(Uuid::fromInteger(strval(14)));

        $manager->persist($media);
        $manager->flush();
    }
}
