<?php

namespace App\Twig;

use App\Entity\User;
use App\Idm\IdmManager;
use App\Idm\IdmRepository;
use App\Repository\UserImageRepository;
use Ramsey\Uuid\Uuid;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigTest;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;

class UserExtension extends AbstractExtension
{
    private IdmRepository $userRepo;
    private UserImageRepository $imageRepo;
    private UploaderHelper $ulh;

    public function __construct(IdmManager $manager, UserImageRepository $imageRepo, UploaderHelper $ulh)
    {
        $this->userRepo = $manager->getRepository(User::class);
        $this->imageRepo = $imageRepo;
        $this->ulh = $ulh;
    }

    /**
     * {@inheritdoc}
     */
    public function getTests()
    {
        return [
            new TwigTest('valid_user', [$this, 'validUser'])
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters()
    {
        return [
            new TwigFilter('username', [$this, 'getUserName']),
            new TwigFilter('userimage', [$this, 'getUserImage']),
        ];
    }

    public function getUserName($userId)
    {
        if (!Uuid::isValid($userId))
            return "";

        $user = $this->userRepo->findOneById($userId);

        if (empty($user))
            return "";

        return $user->getNickname();
    }

    public function getUserImage($userId)
    {
        if (!Uuid::isValid($userId))
            return "";

        $image = $this->imageRepo->findOneByUuid($userId);
        if (empty($image))
            return "";

        return $this->ulh->asset($image, 'imageFile');
    }

    public function validUser($userId)
    {
        if (!Uuid::isValid($userId))
            return false;

        $user = $this->userRepo->findOneById($userId);
        return !empty($user);
    }
}