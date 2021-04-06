<?php

namespace App\Twig;

use App\Entity\User;
use App\Idm\IdmManager;
use App\Service\GroupService;
use Ramsey\Uuid\Uuid;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigTest;

class UserExtension extends AbstractExtension
{
    private $userRepo;

    public function __construct(IdmManager $manager)
    {
        $this->userRepo = $manager->getRepository(User::class);
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
            new TwigFilter('groupname', [$this, 'getGroupname']),
        ];
    }

    public function getUsername($userId)
    {
        if (empty($userId) || !Uuid::isValid($userId))
            return "";

        $user = $this->userRepo->findOneById($userId);

        if (empty($user))
            return "";

        return $user->getNickname();
    }

    public function getGroupname($groupid)
    {
        if (empty($groupid) || !Uuid::isValid($groupid))
            return "";

        return GroupService::getName(Uuid::fromString($groupid));
    }

    public function validUser($userId)
    {
        if (!Uuid::isValid($userId))
            return false;

        $user = $this->userRepo->findOneById($userId);
        return !empty($user);
    }
}