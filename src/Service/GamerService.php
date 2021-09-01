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
    private function getGamer(User $user) : UserGamer
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

        $this->logger->info("Gamer {$user->getNickname()} got payed status set.");
        $gamer->setPayed(new \DateTime());
        $this->em->persist($gamer);
        $this->em->flush();
    }

    public function gamerUnPay(User $user)
    {
        $gamer = $this->getGamer($user);

        if (!$gamer->hasPayed())
            throw new GamerLifecycleException($user, "User not payed yet.");

        $this->logger->info("Gamer {$user->getNickname()} got payed status cleared.");
        $gamer->setPayed(null);
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
}