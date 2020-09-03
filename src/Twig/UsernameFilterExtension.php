<?php

namespace App\Twig;

use App\Service\UserService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class UsernameFilterExtension extends AbstractExtension
{
    private $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function getFilters()
    {
        return [
            new TwigFilter('user', [$this, 'getUsername']),
        ];
    }

    public function getUsername($userId)
    {
        $user = $this->userService->getUserInfoByUuid($userId);

        if (empty($user))
            return "";

        return $user->getNickname();
    }
}