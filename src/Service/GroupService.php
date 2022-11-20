<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\UserAdmin;
use App\Entity\UserGamer;
use App\Idm\Collection;
use App\Idm\IdmManager;
use App\Idm\IdmRepository;
use App\Repository\UserAdminsRepository;
use App\Repository\UserGamerRepository;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class GroupService
{
    final public const GROUP_NEWSLETTER = '083ae2b4-0351-4f82-936c-4f8716cd790f';
    final public const GROUP_REGISTERED = 'f0c2d3c2-5860-4569-9d13-0dc0d2766117';
    final public const GROUP_PAID = '8ae23ac3-ced7-40f7-b092-79da065f0b02';
    final public const GROUP_PAID_NO_SEAT = '5ec12941-0448-4a6f-a194-fd9ce2874925';
    final public const GROUP_REGISTERED_NOT_PAID = '225db67c-54ae-4f30-a3a9-6589d8336c8a';
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
        self::GROUP_REGISTERED => [
            self::NAME => 'Registriert',
            self::METHOD => 'getGamer',
            self::FILTER => ['registered' => true],
        ],
        self::GROUP_PAID => [
            self::NAME => 'Bezahlt',
            self::METHOD => 'getGamer',
            self::FILTER => ['paid' => true],
        ],
        self::GROUP_PAID_NO_SEAT => [
            self::NAME => 'Bezahlt ohne Sitzplatz',
            self::METHOD => 'getGamer',
            self::FILTER => ['paid' => true, 'seat' => false],
        ],
        self::GROUP_REGISTERED_NOT_PAID => [
            self::NAME => 'Registriert ohne Bezahlung',
            self::METHOD => 'getGamer',
            self::FILTER => ['registered' => true, 'paid' => false],
        ],
        self::GROUP_ADMINS => [
            self::NAME => 'KLMS Admins',
            self::METHOD => 'getAdmin',
            self::FILTER => [],
        ],
    ];

    private readonly IdmRepository $userRepo;
    private readonly UserGamerRepository $gamerRepo;
    private readonly UserAdminsRepository $adminRepo;

    public function __construct(IdmManager $manager, UserGamerRepository $gamerRepo, UserAdminsRepository $adminRepo)
    {
        $this->gamerRepo = $gamerRepo;
        $this->adminRepo = $adminRepo;
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

    public function query(UuidInterface $group): array
    {
        if (!self::groupExists($group)) {
            return [];
        }
        $config = self::GROUP_SETTING[$group->toString()];

        return $this->{$config[self::METHOD]}($config[self::FILTER]);
    }

    private function getGamer(array $filter): array
    {
        $registered = $filter['registered'] ?? null;
        $paid = $filter['paid'] ?? null;
        $seat = $filter['seat'] ?? null;
        $gamer = $this->gamerRepo->findByState($registered, $paid, $seat);
        $gamer = array_map(fn (UserGamer $ug) => $ug->getUuid(), $gamer);

        return $this->userRepo->findById($gamer);
    }

    private function getAdmin(array $filter): array
    {
        $admins = $this->adminRepo->findAll();
        $admins = array_filter($admins, fn (UserAdmin $a) => !empty($a->getPermissions()));
        $ids = array_map(fn (UserAdmin $a) => $a->getUuid(), $admins);

        return $this->userRepo->findById($ids);
    }

    private function getIdm(array $filter): Collection
    {
        return $this->userRepo->findBy($filter);
    }
}
