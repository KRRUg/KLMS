<?php

namespace App\Service;

use App\Entity\Clan;
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
    private readonly LoggerInterface $logger;
    private readonly EntityManagerInterface $em;
    private readonly UserGamerRepository $repo;
    private readonly IdmRepository $userRepo;
    private readonly IdmRepository $clanRepo;
    private readonly EmailService $emailService;
    private readonly SettingService $settingService;

    final public const DATETIME_FORMAT = 'Y.m.d H:i:s';

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
        $this->clanRepo = $manager->getRepository(Clan::class);
    }

    /**
     * Returns a UserGamer object of a user. Does not perform a EntityManager flush operation!
     *
     * @param User $user The user to get the UserGamer of
     *
     * @return UserGamer A (potentially created) UserGamer object
     */
    private function getOrCreateGamer(User $user): UserGamer
    {
        $userGamer = $this->repo->findByUser($user);
        if ($userGamer) {
            return $userGamer;
        }

        $this->logger->info("Creating UserGamer for User {$user->getNickname()}.");
        $userGamer = new UserGamer($user->getUuid());
        $this->em->persist($userGamer);

        return $userGamer;
    }

    public function getGamer(User $user): ?UserGamer
    {
        return $this->repo->findByUser($user);
    }

    public function gamerRegister(User $user)
    {
        $gamer = $this->getOrCreateGamer($user);

        if ($gamer->hasRegistered()) {
            throw new GamerLifecycleException($user, 'User already registered.');
        }

        $this->logger->info("Gamer {$user->getNickname()} got registration status set.");
        $gamer->setRegistered(new DateTime());
        $this->em->persist($gamer);
        $this->em->flush();
    }

    public function gamerUnregister(User $user)
    {
        $gamer = $this->getOrCreateGamer($user);

        if (!$gamer->hasRegistered()) {
            throw new GamerLifecycleException($user, 'User not registered yet.');
        }

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

        if (!$gamer->hasRegistered()) {
            throw new GamerLifecycleException($user, 'User not registered yet.');
        }

        if ($gamer->hasPaid()) {
            throw new GamerLifecycleException($user, 'User already paid.');
        }

        $this->logger->info("Gamer {$user->getNickname()} got paid status set.");

        $gamer->setPaid(new DateTime());
        $this->em->persist($gamer);
        $this->em->flush();

        if ($this->settingService->isSet('site.title')) {
            $message = "Wir haben dein Geld erhalten! Der Sitzplan für die Veranstaltung \"{$this->settingService->get('site.title')}\" wurde freigeschaltet.";
        } else {
            $message = 'Wir haben dein Geld erhalten! Der Sitzplan wurde freigeschaltet.';
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

        if (!$gamer->hasPaid()) {
            throw new GamerLifecycleException($user, 'User not paid yet.');
        }

        $this->logger->info("Gamer {$user->getNickname()} got paid status cleared.");
        $gamer->setPaid(null);
        $gamer->setCheckedIn(null);
        $this->em->persist($gamer);
        $this->em->flush();
    }

    public function gamerCheckIn(User $user)
    {
        $gamer = $this->getOrCreateGamer($user);

        if (!$gamer->hasRegistered()) {
            throw new GamerLifecycleException($user, 'User not registered yet.');
        }

        if (!$gamer->hasPaid()) {
            throw new GamerLifecycleException($user, 'User not paid yet.');
        }

        if (!$user->getPersonalDataConfirmed()) {
            throw new GamerLifecycleException($user, 'PersonalData from User not confirmed yet.');
        }

        $this->logger->info("Gamer {$user->getNickname()} checkIn status set.");

        $gamer->setCheckedIn(new DateTime());
        $this->em->persist($gamer);
        $this->em->flush();
    }

    public function gamerCheckOut(User $user)
    {
        $gamer = $this->getOrCreateGamer($user);

        if (!$gamer->hasRegistered()) {
            throw new GamerLifecycleException($user, 'User not registered yet.');
        }

        if (!$gamer->hasPaid()) {
            throw new GamerLifecycleException($user, 'User not paid yet.');
        }

        if (!$gamer->hasCheckedIn()) {
            throw new GamerLifecycleException($user, 'User not checkedIn yet.');
        }

        $this->logger->info("Gamer {$user->getNickname()} checkOut status set.");

        $gamer->setCheckedIn(null);
        $this->em->persist($gamer);
        $this->em->flush();
    }

    public function gamerGetStatus(User $user): UserGamer
    {
        $gamer = $this->repo->findByUser($user);
        // clone to detach from Doctrine to avoid bypassing this service
        return clone $gamer;
    }

    public function gamerHasPaid(User $user): bool
    {
        $gamer = $this->getGamer($user) ?? false;

        return $gamer && $gamer->hasPaid();
    }

    public function getGamers(bool $associative = true): array
    {
        $gamers = $this->repo->findAll();
        $gamers = array_filter($gamers, fn (UserGamer $g) => $g->hasRegistered());
        $ids = array_map(fn (UserGamer $g) => $g->getUuid()->toString(), $gamers);
        $gamers = array_combine($ids, $gamers);
        $users = $this->userRepo->findById($ids);

        $ret = [];
        foreach ($users as $user) {
            $uuid = $user->getUuid()->toString();
            if ($associative) {
                $ret[$uuid] = ['user' => $user, 'status' => $gamers[$uuid]];
            } else {
                $ret[] = ['user' => $user, 'status' => $gamers[$uuid]];
            }
        }

        return $ret;
    }

    public function getClans(bool $associative = true): array
    {
        $gamers = $this->getGamers(false);
        $clan_uuid = [];
        foreach ($gamers as $gamer) {
            $g = $gamer['user'];
            foreach ($g->getClans()->toUuidArray() as $clan) {
                $clan_uuid[] = $clan->getUuid();
            }
        }
        $clans = $this->clanRepo->findById($clan_uuid);
        if ($associative) {
            // TODO implement me
            return $clans;
        } else {
            return $clans;
        }
    }

    public function getUserFromGamer(UserGamer $userGamer): ?User
    {
        return $this->userRepo->findOneById($userGamer->getUuid());
    }
}
