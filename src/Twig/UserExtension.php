<?php

namespace App\Twig;

use App\Service\UserService;
use Ramsey\Uuid\Uuid;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigTest;

class UserExtension extends AbstractExtension
{
    private $userService;

    public function __construct(UserService $userService)
    {
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
            new TwigFilter('username', [$this, 'getUsername']),
        ];
    }

    public function getUsername($userId)
    {
        if (!Uuid::isValid($userId))
            return "";

        $user = $this->userService->getUserInfoByUuid($userId);

        if (empty($user))
            return "";

        return $user->getNickname();
    }

    public function validUser($userId)
    {
        if (!Uuid::isValid($userId))
            return false;

        $user = $this->userService->getUserInfoByUuid($userId);
        return !empty($user);
    }
}