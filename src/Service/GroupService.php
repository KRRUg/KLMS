<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\UserGamer;
use App\Idm\IdmManager;
use App\Idm\IdmRepository;
use App\Idm\LazyLoaderCollection;
use App\Repository\UserGamerRepository;

class GroupService
{
    const GROUP_NEWSLETTER = 'newsletter';
    const GROUP_REGISTERED = 'registered';
    const GROUP_PAYED = 'payed';
    const GROUP_PAYED_NO_SEAT = 'pay_no_seat';
    const GROUP_REGISTERED_NOT_PAYED = 'reg_no_pay';

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

    public function getGroups(): array
    {
        $result = [];
        foreach (self::GROUP_SETTING as $group => $config) {
            $result[$group] = $config[self::NAME];
        }
        return $result;
    }

    public function query(string $group)
    {
        if (!array_key_exists($group, self::GROUP_SETTING)) {
            return [];
        }
        $config = self::GROUP_SETTING[$group];
        return $this->{$config[self::METHOD]}($config[self::FILTER]);
    }

    private function getGamer(array $filter)
    {
        $registered = $filter['registered'] ?? null;
        $payed = $filter['payed'] ?? null;
        $seat = $filter['seat'] ?? null;
        $gamer = $this->gamerRepo->findByState($registered, $payed, $seat);
        $gamer = array_map(function (UserGamer $ug) { return $ug->getId(); }, $gamer);
        return $this->userRepo->findById($gamer);
    }

    private function getIdm(array $filter)
    {
        return $this->userRepo->findBy($filter);
    }
}