<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\UserAdmin;
use App\Idm\IdmManager;
use App\Idm\IdmPagedCollection;
use App\Idm\IdmRepository;
use App\Repository\UserAdminsRepository;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class GroupService
{
    final public const GROUP_NEWSLETTER = '083ae2b4-0351-4f82-936c-4f8716cd790f';
    final public const GROUP_PAID = '8ae23ac3-ced7-40f7-b092-79da065f0b02';
    final public const GROUP_PAID_NO_SEAT = '5ec12941-0448-4a6f-a194-fd9ce2874925';
    final public const GROUP_ADMINS = 'c74aaa27-c501-454d-a8cd-0026ff671f53';

    private const NAME = 'name';
    private const METHOD = 'method';
    private const FILTER = 'filter';

    private const GROUP_SETTING = [
        self::GROUP_NEWSLETTER => [
            self::NAME => 'Newsletter',
            self::METHOD => 'getIdm',
            self::FILTER => ['infoMails' => true],
        ],
        self::GROUP_PAID => [
            self::NAME => 'Bezahlt',
            self::METHOD => 'getGamer',
            self::FILTER => [],
        ],
        self::GROUP_PAID_NO_SEAT => [
            self::NAME => 'Bezahlt ohne Sitzplatz',
            self::METHOD => 'getGamer',
            self::FILTER => ['seat' => false],
        ],
        self::GROUP_ADMINS => [
            self::NAME => 'KLMS Admins',
            self::METHOD => 'getAdmin',
            self::FILTER => [],
        ],
    ];

    private readonly IdmRepository $userRepo;
    private readonly UserAdminsRepository $adminRepo;
    private readonly TicketService $ticketService;
    private readonly SeatmapService $seatmapService;

    public function __construct(IdmManager $manager, TicketService $ticketService, SeatmapService $seatmapService, UserAdminsRepository $adminRepo)
    {
        $this->adminRepo = $adminRepo;
        $this->ticketService = $ticketService;
        $this->seatmapService = $seatmapService;
        $this->userRepo = $manager->getRepository(User::class);
    }

    public static function getGroups(): array
    {
        $result = [];
        foreach (self::GROUP_SETTING as $group => $config) {
            $result[$config[self::NAME]] = Uuid::fromString($group);
        }

        return $result;
    }

    public static function groupExists(UuidInterface $group): bool
    {
        return array_key_exists($group->toString(), self::GROUP_SETTING);
    }

    public static function getName(UuidInterface $group): string
    {
        return self::GROUP_SETTING[$group->toString()][self::NAME] ?? '';
    }

    // TODO once Collection Interface is done for IDM service, change this to Collection
    public function query(UuidInterface $group): array | IdmPagedCollection
    {
        if (!self::groupExists($group)) {
            return [];
        }
        $config = self::GROUP_SETTING[$group->toString()];

        return $this->{$config[self::METHOD]}($config[self::FILTER]);
    }

    private function getGamer(array $filter): array
    {
        $seat = $filter['seat'] ?? null;
        $ticketOwners = $this->ticketService->queryUserUuids(TicketState::REDEEMED);

        if (!is_null($seat)) {
            $seatOwners = $this->seatmapService->getSeatOwners();
            if ($seat) {
                // intersect gamer on lan with seat owners
                $uuids = array_intersect($ticketOwners, $seatOwners);
            } else {
                // gamer on lan minus seat owners
                $uuids = array_diff($ticketOwners, $seatOwners);
            }
        }
        return $this->userRepo->findById($uuids);
    }

    private function getAdmin(array $filter): array
    {
        $admins = $this->adminRepo->findAll();
        $admins = array_filter($admins, fn (UserAdmin $a) => !empty($a->getPermissions()));
        $ids = array_map(fn (UserAdmin $a) => $a->getUuid(), $admins);

        return $this->userRepo->findById($ids);
    }

    private function getIdm(array $filter): IdmPagedCollection
    {
        return $this->userRepo->findBy($filter);
    }
}
