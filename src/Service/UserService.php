<?php

namespace App\Service;

use App\Entity\User;
use App\Helper\EmailRecipient;
use App\Idm\IdmManager;
use App\Idm\IdmRepository;
use Ramsey\Uuid\UuidInterface;

class UserService
{
    private IdmManager $manager;
    private IdmRepository $userRepo;
    private EmailService $emailService;
    private TokenService $tokenService;

    public function __construct(IdmManager $manager, EmailService $emailService, TokenService $tokenService)
    {
        $this->manager = $manager;
        $this->userRepo = $manager->getRepository(User::class);
        $this->emailService = $emailService;
        $this->tokenService = $tokenService;
    }

    public function registerUser(User $user): bool
    {
        $this->manager->persist($user);
        $this->manager->flush();
        $token = $this->tokenService->generateToken($user->getUuid(), self::TOKEN_MAIL_CONFIRM_STRING);
        return $this->emailService->scheduleHook(
            EmailService::APP_HOOK_REGISTRATION_CONFIRM,
            EmailRecipient::fromUser($user),
            ['token' => $token]
        );
    }

    public function sendPWRequest(User $user): bool
    {
        $token = $this->tokenService->generateToken($user->getUuid(), self::TOKEN_PW_RESET_STRING);
        return $this->emailService->scheduleHook(
            EmailService::APP_HOOK_RESET_PW,
            EmailRecipient::fromUser($user),
            ['token' => $token]
        );
    }
}