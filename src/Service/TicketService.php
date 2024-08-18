<?php

namespace App\Service;

use App\Entity\Ticket;
use App\Entity\User;
use App\Exception\TicketLivecycleException;
use App\Idm\IdmManager;
use App\Idm\IdmRepository;
use App\Repository\TicketRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Ramsey\Uuid\UuidInterface;

class TicketService
{
    private readonly TicketRepository $ticketRepository;
    private readonly IdmRepository $userRepo;
    private EntityManagerInterface $em;

    public const CODE_REGEX = '^([0-9A-Za-z]{5}-?){2}[0-9A-Za-z]{5}$';

    public function __construct(TicketRepository $ticketRepository, IdmManager $iem, EntityManagerInterface $em)
    {
        $this->ticketRepository = $ticketRepository;
        $this->em = $em;
        $this->userRepo = $iem->getRepository(User::class);
    }

    /**
     * @param User|UuidInterface $user
     * @return bool true if user has a ticket
     */
    public function isUserRegistered(User|UuidInterface $user): bool
    {
        return $this->getTicketUser($user) != null;
    }

    /**
     * @param User|UuidInterface $user
     * @return bool true if user has a punched ticket
     */
    public function isUserPunched(User|UuidInterface $user): bool
    {
        $ticket = $this->getTicketUser($user);
        return $ticket && $ticket->isPunched();
    }

    public function getTicketUser(User|UuidInterface $user): ?Ticket
    {
        $uuid = $user instanceof User ? $user->getUuid() : $user;
        return $this->ticketRepository->findOneByRedeemer($uuid);
    }

    public function getTicketId(int $id): ?Ticket
    {
        return $this->ticketRepository->find($id);
    }

    public function getTicketStateUser(User|UuidInterface $user): ?TicketState
    {
        $ticket = $this->getTicketUser($user);
        if (empty($ticket)) { return null; }
        return $ticket->getState();
    }

    public function getTicketCode(string $code): ?Ticket
    {
        return $this->ticketRepository->findOneByCode($code);
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

    private static function createCode(): string
    {
        $abc = array_merge(range(0, 9), range('A', 'Z'));
        $code = '';
        for ($i = 0; $i < 15; $i++) {
            if ($i > 0 && $i % 5 == 0) {
                $code .= '-';
            }
            $code .= $abc[mt_rand(0, count($abc) - 1)];
        }
        return $code;
    }

    private function persistTicket(Ticket $ticket): void
    {
        if ($ticket->getCode()) {
            $this->em->persist($ticket);
            $this->em->flush();
        } else {
            while (true) {
                $ticket->setCode(self::createCode());
                // this is not entirely sound but hopefully good enough (could better be done with SQL update in the registry)
                $this->em->persist($ticket);
                if ($this->ticketRepository->count(['code' => $ticket->getCode()]) == 0) {
                    $this->em->flush();
                    break;
                }
            }
        }
    }

    public function registerUser(User|UuidInterface $user): Ticket
    {
        $uuid = $user instanceof User ? $user->getUuid() : $user;
        // check for existing ticket
        $ticket = $this->getTicketUser($uuid);
        if ($ticket) {
            return $ticket;
        }

        // create new ticket
        $now = new DateTimeImmutable();
        $ticket = (new Ticket())
            ->setCreatedAt($now)
            ->setRedeemedAt($now)
            ->setRedeemer($uuid);

        $this->persistTicket($ticket);
        return $ticket;
    }

    public function unregisterUser(User|UuidInterface $user, bool $deleteTicket = true): bool
    {
        $ticket = $this->getTicketUser($user);
        if ($ticket == null){
            return false;
        }

        if ($deleteTicket) {
            $this->deleteTicket($ticket);
        } else {
            $this->unassignTicket($ticket);
        }
        return true;
    }

    public function unassignTicket(Ticket $ticket): void
    {
        $ticket
            ->setRedeemer(null)
            ->setRedeemedAt(null)
            ->setPunchedAt(null);
        $this->em->persist($ticket);
        $this->em->flush();
    }

    public function deleteTicket(Ticket $ticket): void
    {
        if ($ticket->getShopOrderPosition()) {
            throw new TicketLivecycleException($ticket);
        }
        $this->em->remove($ticket);
        $this->em->flush();
    }

    public function createTicket(): Ticket
    {
        $ticket = (new Ticket())
            ->setCreatedAt(new DateTimeImmutable());
        $this->persistTicket($ticket);
        return $ticket;
    }

    public function redeemTicket(Ticket|string $ticket, User|UuidInterface $user): bool
    {
        $uuid = $user instanceof User ? $user->getUuid() : $user;
        $ticket = $ticket instanceof Ticket ? $ticket : $this->ticketRepository->findOneByCode($ticket);
        if (is_null($ticket)) {
            return false;
        }
        $state = $ticket->getState();
        if (is_null($state)) {
            throw new TicketLivecycleException($ticket);
        }
        if ($state == TicketState::NEW && !$this->isUserRegistered($uuid)) {
            $ticket
                ->setRedeemer($uuid)
                ->setRedeemedAt(new DateTimeImmutable());
            $this->em->persist($ticket);
            $this->em->flush();
            return true;
        }
        return false;
    }

    public function punchTicketCode(string $code): bool
    {
        $ticket = $this->ticketRepository->findOneByCode($code);
        if (is_null($ticket)) {
            return false;
        }
        return $this->punchTicket($ticket);
    }

    public function punchTicketUser(User|UuidInterface $user): bool
    {
        $ticket = $this->getTicketUser($user);
        if (is_null($ticket)) {
            return false;
        }
        return $this->punchTicket($ticket);
    }

    public function punchTicket(Ticket $ticket): bool
    {
        $state = $ticket->getState();
        if (is_null($state)) {
            throw new TicketLivecycleException($ticket);
        }
        if ($state == TicketState::REDEEMED) {
            $ticket
                ->setPunchedAt(new DateTimeImmutable());
            $this->em->flush();
            return true;
        }
        return false;
    }

    public function unpunchTicketUser(User|UuidInterface $user): bool
    {
        $ticket = $this->getTicketUser($user);
        if (is_null($ticket)) {
            return false;
        }
        return $this->unpunchTicket($ticket);
    }

    public function unpunchTicket(Ticket $ticket): bool
    {
        $state = $ticket->getState();
        if (is_null($state)) {
            throw new TicketLivecycleException($ticket);
        }
        if ($state == TicketState::PUNCHED) {
            $ticket
                ->setPunchedAt(null);
            $this->em->flush();
            return true;
        }
        return false;
    }

    public function userByTicket(Ticket $ticket): ?User
    {
        if (empty($ticket->getRedeemer())) {
            return null;
        }

        return $this->userRepo->findOneById($ticket->getRedeemer());
    }

    /**
     * @param TicketState $state
     * @return Ticket[]
     */
    public function queryTickets(TicketState $state = TicketState::NEW): array
    {
        return $this->ticketRepository->findByState($state);
    }

    /**
     * @param TicketState $state
     * @return UuidInterface[]
     */
    public function queryUserUuids(TicketState $state): array
    {
        return array_map(function (Ticket $t) { return $t->getRedeemer(); }, $this->ticketRepository->findByState($state));
    }

    public function countTickets(): int
    {
        return $this->ticketRepository->count([]);
    }

    public function countFreeTickets(): int
    {
        return $this->ticketRepository->count(['redeemer' => null]);
    }

    public function countRedeemedTickets(): int
    {
        return $this->ticketRepository->countRedeemed();
    }

    public function countPunchedTickets(): int
    {
        return $this->ticketRepository->countPunched();
    }

    public function hasInvalid(): bool
    {
        return $this->ticketRepository->countInvalid() != 0;
    }
}