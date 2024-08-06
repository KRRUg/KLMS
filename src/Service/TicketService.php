<?php

namespace App\Service;

use App\Entity\Ticket;
use App\Entity\User;
use App\Repository\TicketRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\UuidInterface;

class TicketService
{
    private readonly TicketRepository $ticketRepository;

    private EntityManagerInterface $em;

    public const CODE_REGEX = '^([0-9A-Za-z]{5}-?){2}[0-9A-Za-z]{5}$';

    public function __construct(TicketRepository $ticketRepository, EntityManagerInterface $em)
    {
        $this->ticketRepository = $ticketRepository;
        $this->em = $em;
    }

    /**
     * @param User|UuidInterface $user
     * @return bool true if user has at ticket
     */
    public function userRegistered(User|UuidInterface $user): bool
    {
        $uuid = $user instanceof User ? $user->getUuid() : $user;
        return $this->ticketRepository->ticketByRedeemer($uuid) != null;
    }

    public function ticketCodeValid(string $code): bool
    {
       return preg_match('/'.self::CODE_REGEX."/", $code)
           && $this->ticketRepository->count(['code' => $code]) > 0;
    }

    public function ticketCodeUnused(string $code): bool
    {
        return preg_match('/'.self::CODE_REGEX."/", $code)
            && $this->ticketRepository->count(['code' => $code, 'redeemer' => null]) > 0;
    }

    public function redeemTicket(string $code, User|UuidInterface $user): bool
    {
        $uuid = $user instanceof User ? $user->getUuid() : $user;
        $ticket = $this->ticketRepository->findOneBy(['code' => $code]);
        if ($ticket && !$ticket->isRedeemed() && !$this->userRegistered($uuid)) {
            $ticket
                ->setRedeemer($uuid)
                ->setRedeemedAt(new DateTimeImmutable());
            $this->em->persist($ticket);
            $this->em->flush();
            return true;
        }
        return false;
    }
}