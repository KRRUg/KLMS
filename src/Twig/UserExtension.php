<?php

namespace App\Twig;

use App\Entity\User;
use App\Idm\IdmManager;
use App\Idm\IdmRepository;
use App\Repository\UserImageRepository;
use App\Service\UserService;
use Ramsey\Uuid\Uuid;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigTest;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;

class UserExtension extends AbstractExtension
{
    private IdmRepository $userRepo;
    private UserService $userService;

    public function __construct(IdmManager $manager, UserService $userService)
    {
        $this->userRepo = $manager->getRepository(User::class);
        $this->userService = $userService;
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
            new TwigFilter('user', [$this, 'getUser']),
            new TwigFilter('username', [$this, 'getUserName']),
            new TwigFilter('user_image', [$this, 'getUserImage']),
        ];
    }

    public function getUser($userId): ?User
    {
        if (!Uuid::isValid($userId))
            return null;

        return $this->userRepo->findOneById($userId);
    }

    public function getUserName($userId): string
    {
        $user = $this->getUser($userId);

        if (empty($user))
            return "";

        return $user->getNickname();
    }

    public function getUserImage(User $user): string
    {
        return $this->userService->getUserImage($user);
    }

    public function validUser($userId): bool
    {
        return !empty($this->getUser($userId));
    }
}