<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\UserGamer;
use App\Idm\IdmManager;
use App\Idm\IdmRepository;
use App\Repository\UserGamerRepository;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\UuidInterface;

class GroupService
{
    const GROUP_NEWSLETTER = '083ae2b4-0351-4f82-936c-4f8716cd790f';
    const GROUP_REGISTERED = 'f0c2d3c2-5860-4569-9d13-0dc0d2766117';
    const GROUP_PAYED = '8ae23ac3-ced7-40f7-b092-79da065f0b02';
    const GROUP_PAYED_NO_SEAT = '5ec12941-0448-4a6f-a194-fd9ce2874925';
    const GROUP_REGISTERED_NOT_PAYED = '225db67c-54ae-4f30-a3a9-6589d8336c8a';

    private const NAME = 'name';
    private const METHOD = 'method';
    private const FILTER = 'filter';

    private const GROUP_SETTING = [
        self::GROUP_NEWSLETTER => [
            self::NAME => "Newsletter",
            self::METHOD => "getIdm",
            self::FILTER => ["infoMails" => true]
        ],
        self::GROUP_REGISTERED => [
            self::NAME => "Registriert",
            self::METHOD => "getGamer",
            self::FILTER => ["registered" => true]
        ],
        self::GROUP_PAYED => [
            self::NAME => "Bezahlt",
            self::METHOD => "getGamer",
            self::FILTER => ["payed" => true]
        ],
        self::GROUP_PAYED_NO_SEAT => [
            self::NAME => "Bezahlt ohne Sitzplatz",
            self::METHOD => "getGamer",
            self::FILTER => ["payed" => true, "seat" => false]
        ],
        self::GROUP_REGISTERED_NOT_PAYED => [
            self::NAME => "Registriert ohne Bezahlung",
            self::METHOD => "getGamer",
            self::FILTER => ["registered" => true, "payed" => false]
        ],
    ];

    private IdmRepository $userRepo;
    private UserGamerRepository $gamerRepo;

    public function __construct(IdmManager $manager, UserGamerRepository $gamerRepo)
    {
        $this->gamerRepo = $gamerRepo;
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

    public function query(UuidInterface $group)
    {
        if (!self::groupExists($group)) {
            return [];
        }
        $config = self::GROUP_SETTING[$group->toString()];
        return $this->{$config[self::METHOD]}($config[self::FILTER]);
    }

    private function getGamer(array $filter)
    {
        $registered = $filter['registered'] ?? null;
        $payed = $filter['payed'] ?? null;
        $seat = $filter['seat'] ?? null;
        $gamer = $this->gamerRepo->findByState($registered, $payed, $seat);
	$gamer = array_map(function (UserGamer $ug) { return $ug->getUuid(); }, $gamer);
	return $this->userRepo->findById($gamer);
    }

    private function getIdm(array $filter)
    {
        return $this->userRepo->findBy($filter);
    }
}
