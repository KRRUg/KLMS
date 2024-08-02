<?php

namespace App\Twig;

use App\Entity\User;
use App\Idm\IdmManager;
use App\Idm\IdmRepository;
use App\Service\GroupService;
use App\Service\UserService;
use Ramsey\Uuid\Uuid;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigTest;

class UserExtension extends AbstractExtension
{
    private readonly IdmRepository $userRepo;
    private readonly UserService $userService;

    public function __construct(IdmManager $manager, UserService $userService)
    {
        $this->userRepo = $manager->getRepository(User::class);
        $this->userService = $userService;
    }

    /**
     * {@inheritdoc}
     */
    public function getTests(): array
    {
        return [
            new TwigTest('valid_user', $this->validUser(...)),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('user', $this->getUser(...)),
            new TwigFilter('username', $this->getUserName(...)),
            new TwigFilter('user_image', $this->getUserImage(...)),
            new TwigFilter('groupname', $this->getGroupname(...)),
        ];
    }

    public function getUser($userId): ?User
    {
        if (empty($userId) || !Uuid::isValid($userId)) {
            return null;
        }

        return $this->userRepo->findOneById($userId);
    }

    public function getUserName($userId): string
    {
        $user = $this->getUser($userId);

        if (empty($user)) {
            return '';
        }

        return $user->getNickname();
    }

    public function getGroupname($groupid): string
    {
        if (empty($groupid) || !Uuid::isValid($groupid)) {
            return '';
        }

        return GroupService::getName(Uuid::fromString($groupid));
    }

    public function getUserImage(?User $user): string
    {
        if (empty($user)) {
            return '';
        }
        return $this->userService->getUserImage($user) ?? '';
    }

    public function validUser($userId): bool
    {
        return !empty($this->getUser($userId));
    }
}
