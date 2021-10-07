<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\UserGamer;
use App\Exception\GamerLifecycleException;
use App\Helper\EmailRecipient;
use App\Idm\IdmManager;
use App\Idm\IdmRepository;
use App\Repository\UserGamerRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class GamerService
{
    private LoggerInterface $logger;
    private EntityManagerInterface $em;
    private UserGamerRepository $repo;
    private IdmRepository $userRepo;
    private EmailService $emailService;
    private SettingService $settingService;

    const DATETIME_FORMAT = 'Y.m.d H:i:s';

    /*
     * Clarification: User is the Symfony User with information from IDM, while Gamer is the local KLMS information,
     * i.e. the status w.r.t this KLMS instance.
     */

    public function __construct(LoggerInterface $logger,
                                EntityManagerInterface $em,
                                UserGamerRepository $repo,
                                EmailService $emailService,
                                SettingService $settingService,
                                IdmManager $manager)
    {
        $this->logger = $logger;
        $this->em = $em;
        $this->repo = $repo;
        $this->emailService = $emailService;
        $this->settingService = $settingService;
        $this->userRepo = $manager->getRepository(User::class);
    }

    /**
     * Returns a UserGamer object of an user. Does not perform a EntityManager flush operation!
     * @param User $user The user to get the UserGamer of
     * @return UserGamer A (potentially created) UserGamer object
     */
    private function getOrCreateGamer(User $user) : UserGamer
    {
        $userGamer = $this->repo->findByUser($user);
        if ($userGamer)
            return $userGamer;

        $this->logger->info("Creating UserGamer for User {$user->getNickname()}.");
        $userGamer = new UserGamer($user->getUuid());
        $this->em->persist($userGamer);
        return $userGamer;
    }

    private function getGamer(User $user) : ?UserGamer
    {
        return $this->repo->findByUser($user);
    }

    public function gamerRegister(User $user)
    {
        $gamer = $this->getOrCreateGamer($user);

        if ($gamer->hasRegistered())
            throw new GamerLifecycleException($user, "User already registered.");

        $this->logger->info("Gamer {$user->getNickname()} got registration status set.");
        $gamer->setRegistered(new DateTime());
        $this->em->persist($gamer);
        $this->em->flush();
    }

    public function gamerUnregister(User $user)
    {
        $gamer = $this->getOrCreateGamer($user);

        if (!$gamer->hasRegistered())
            throw new GamerLifecycleException($user, "User not registered yet.");

        $this->logger->info("Gamer {$user->getNickname()} got registration status cleared.");
        $gamer->setRegistered(null);
        $gamer->setPaid(null);
        $gamer->setCheckedIn(null);
        $this->em->persist($gamer);
        $this->em->flush();
    }

    public function gamerHasRegistered(User $user): bool
    {
        $gamer = $this->getGamer($user) ?? false;
        return $gamer && $gamer->hasRegistered();
    }

    public function gamerPay(User $user)
    {
        $gamer = $this->getOrCreateGamer($user);

        if (!$gamer->hasRegistered())
            throw new GamerLifecycleException($user, "User not registered yet.");

        if ($gamer->hasPaid())
            throw new GamerLifecycleException($user, "User already paid.");

        $this->logger->info("Gamer {$user->getNickname()} got paid status set.");

        $gamer->setPaid(new DateTime());
        $this->em->persist($gamer);
        $this->em->flush();

        if ($this->settingService->isSet('site.title')) {
            $message = "Wir haben dein Geld erhalten! Der Sitzplan für die Veranstaltung \"{$this->settingService->get('site.title')}\" wurde freigeschaltet.";
        } else {
            $message = "Wir haben dein Geld erhalten! Der Sitzplan wurde freigeschaltet.";
        }
        $this->emailService->scheduleHook(
            EmailService::APP_HOOK_CHANGE_NOTIFICATION,
            EmailRecipient::fromUser($user), [
                'message' => $message,
            ]
        );
    }

    public function gamerUnPay(User $user)
    {
        $gamer = $this->getOrCreateGamer($user);

        if (!$gamer->hasPaid())
            throw new GamerLifecycleException($user, "User not paid yet.");

        $this->logger->info("Gamer {$user->getNickname()} got paid status cleared.");
        $gamer->setPaid(null);
        $gamer->setCheckedIn(null);
        $this->em->persist($gamer);
        $this->em->flush();
    }

    public function gamerGetStatus(User $user): UserGamer
    {
        $gamer = $this->repo->findByUser($user);
        // clone to detach from Doctrine to avoid bypassing this service
        return clone($gamer);
    }

    public function gamerHasPaid(User $user): bool
    {
        $gamer = $this->getGamer($user) ?? false;
        return $gamer && $gamer->hasPaid();
    }

    public function getGamers() : array
    {
        $gamers = $this->repo->findAll();
        $gamers = array_filter($gamers, function (UserGamer $g) { return $g->hasRegistered(); });
        $ids = array_map(function (UserGamer $g) { return $g->getUuid()->toString(); }, $gamers);
        $gamers = array_combine($ids, $gamers);
        $users = $this->userRepo->findById($ids);

        $ret = [];
        foreach ($users as $user) {
            $uuid = $user->getUuid()->toString();
            $ret[$uuid] = ['user' => $user, 'status' => $gamers[$uuid]];
        }
        return $ret;
    }

    public function gamer2Array(UserGamer $userGamer): array
    {
        return [
            'uuid' => $userGamer->getUuid(),
            'registered' => $userGamer->getRegistered() ? $userGamer->getRegistered()->format(self::DATETIME_FORMAT) : null,
            'paid' => $userGamer->getPaid() ? $userGamer->getPaid()->format(self::DATETIME_FORMAT) : null,
            'checkedIn' => $userGamer->getCheckedIn() ? $userGamer->getCheckedIn()->format(self::DATETIME_FORMAT) : null,
        ];
    }
}