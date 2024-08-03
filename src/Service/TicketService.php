<?php

namespace App\Service;

use App\Entity\User;
use App\Repository\TicketRepository;
use Ramsey\Uuid\UuidInterface;

class TicketService
{
    private readonly TicketRepository $ticketRepository;

    /**
     * @param TicketRepository $ticketRepository
     */
    public function __construct(TicketRepository $ticketRepository)
    {
        $this->ticketRepository = $ticketRepository;
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
}