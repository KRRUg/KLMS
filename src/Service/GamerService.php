<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\UserGamer;
use App\Exception\GamerLifecycleException;
use App\Idm\IdmManager;
use App\Idm\IdmRepository;
use App\Repository\UserGamerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class GamerService
{
    private LoggerInterface $logger;
    private EntityManagerInterface $em;
    private UserGamerRepository $repo;
    private IdmRepository $userRepo;

    const DATETIME_FORMAT = 'Y.m.d H:i:s';

    /*
     * Clarification: User is the Symfony User with information from IDM, while Gamer is the local KLMS information,
     * i.e. the status w.r.t this KLMS instance.
     */

    public function __construct(LoggerInterface $logger, EntityManagerInterface $em, UserGamerRepository $repo, IdmManager $manager)
    {
        $this->logger = $logger;
        $this->em = $em;
        $this->repo = $repo;
        $this->userRepo = $manager->getRepository(User::class);
    }

    /**
     * Returns a UserGamer object of an user. Does not perform a EntityManager flush operation!
     * @param User $user The user to get the UserGamer of
     * @return UserGamer A (potentially created) UserGamer object
     */
    public function getGamer(User $user) : UserGamer
    {
        $userGamer = $this->repo->findByUser($user);
        if ($userGamer)
            return $userGamer;

        $this->logger->info("Creating UserGamer for User {$user->getNickname()}.");
        $userGamer = new UserGamer($user->getUuid());
        $this->em->persist($userGamer);
        return $userGamer;
    }

    public function gamerRegister(User $user)
    {
        $gamer = $this->getGamer($user);
        $this->logger->info("Gamer {$user->getNickname()} got registration status set.");
        $gamer->setRegistered(new \DateTime());
        $this->em->persist($gamer);
        $this->em->flush();
    }

    public function gamerUnregister(User $user)
    {
        $gamer = $this->getGamer($user);

        if (!$gamer->hasRegistered())
            throw new GamerLifecycleException($user, "User not registered yet.");

        $this->logger->info("Gamer {$user->getNickname()} got registration status cleared.");
        $gamer->setRegistered(new \DateTime());
        $this->em->persist($gamer);
        $this->em->flush();
    }

    public function gamerPay(User $user)
    {
        $gamer = $this->getGamer($user);

        if (!$gamer->hasRegistered())
            throw new GamerLifecycleException($user, "User not registered yet.");

        $this->logger->info("Gamer {$user->getNickname()} got paid status set.");
        $gamer->setPaid(new \DateTime());
        $this->em->persist($gamer);
        $this->em->flush();
    }

    public function gamerUnPay(User $user)
    {
        $gamer = $this->getGamer($user);

        if (!$gamer->hasPaid())
            throw new GamerLifecycleException($user, "User not paid yet.");

        $this->logger->info("Gamer {$user->getNickname()} got paid status cleared.");
        $gamer->setPaid(null);
        $this->em->persist($gamer);
        $this->em->flush();
    }

    public function getRegisteredGamer()
    {
        $gamer = $this->repo->findAll();
        $gamer = array_filter($gamer, function (UserGamer $gamer) { return $gamer->hasRegistered(); });
        $gamer_uuid = array_map(function (UserGamer $gamer) { return $gamer->getUuid(); }, $gamer);
        return $this->userRepo->findById($gamer_uuid);
    }

    public function getRegisteredGamerWithStatus()
    {
        $gamer = $this->getRegisteredGamer();
        $gamer = array_map(function (User $user) { return ['user' => $user, 'status' => $this->repo->findByUser($user)]; }, $gamer);
        return $gamer;
    }

    public function getPaidGamers()
    {
        $gamer = $this->repo->findAll();
        $gamer = array_filter($gamer, function (UserGamer $gamer) { return $gamer->hasPaid(); });
        $gamer_uuid = array_map(function (UserGamer $gamer) { return $gamer->getUuid(); }, $gamer);
        return $this->userRepo->findById($gamer_uuid);
    }

    public function getPaidGamersWithStatus()
    {
        $gamer = $this->getPaidGamers();
        $gamer = array_map(function (User $user) { return ['user' => $user, 'status' => $this->repo->findByUser($user)]; }, $gamer);
        return $gamer;
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

    public function getUserFromGamer(UserGamer $userGamer): ?User
    {
        return $this->userRepo->findOneById($userGamer->getUuid());
    }

    public function getGamerPaidSeatCount(User $user): int
    {
        $gamer = $this->getGamer($user);
        $seats = 0;
        if(is_int($gamer->getSeatsPaid())) {
            $seats = $gamer->getSeatsPaid();
        }
        return $seats;
    }
}