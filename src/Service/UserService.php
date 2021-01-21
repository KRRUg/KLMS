<?php

namespace App\Service;

use App\Entity\Clan;
use App\Entity\User;
use App\Idm\IdmManager;
use App\Idm\IdmRepository;
use Psr\Log\LoggerInterface;

final class UserService
{
    private IdmManager $im;
    private IdmRepository $userRepo;
    private IdmRepository $clanRepo;
    private LoggerInterface $logger;

    public function __construct(
        LoggerInterface $logger,
        IdmManager $im
    ) {
        $this->logger = $logger;
        $this->im = $im;
        $this->clanRepo = $im->getRepository(Clan::class);
        $this->userRepo = $im->getRepository(User::class);
    }


}
