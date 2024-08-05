<?php

namespace App\Service;

use App\Entity\Teamsite;
use App\Entity\TeamsiteCategory;
use App\Entity\TeamsiteEntry;
use App\Entity\User;
use App\Idm\IdmManager;
use App\Idm\IdmRepository;
use App\Repository\TeamsiteEntryRepository;
use App\Repository\TeamsiteRepository;
use Doctrine\ORM\EntityManagerInterface;

class TeamsiteService implements WipeInterface
{
    private readonly EntityManagerInterface $em;
    private readonly TeamsiteRepository $repo;
    private readonly TeamsiteEntryRepository $entry;
    private readonly IdmRepository $userRepo;
    private readonly UserService $userService;

    public function __construct(
        EntityManagerInterface $em,
        IdmManager $im,
        TeamsiteRepository $repo,
        TeamsiteEntryRepository $entry,
        UserService $userService
    ) {
        $this->em = $em;
        $this->repo = $repo;
        $this->entry = $entry;
        $this->userRepo = $im->getRepository(User::class);
        $this->userService = $userService;
    }

    private const ARRAY_TITLE = 'title';
    private const ARRAY_DESCRIPTION = 'description';
    private const ARRAY_DISPLAYEMAIL = 'displayEmail';
    private const ARRAY_HIDEEMAIL = 'hideEmail';
    private const ARRAY_HIDENAME = 'hideName';
    private const ARRAY_ENTRIES = 'entries';
    private const ARRAY_USER = 'user';
    private const ARRAY_CATEGORY = [
        self::ARRAY_TITLE,
        self::ARRAY_DESCRIPTION,
        self::ARRAY_HIDENAME,
        self::ARRAY_HIDEEMAIL,
        self::ARRAY_ENTRIES,
    ];
    private const ARRAY_ENTRY = [
        self::ARRAY_TITLE,
        self::ARRAY_DESCRIPTION,
        self::ARRAY_DISPLAYEMAIL,
        self::ARRAY_USER,
    ];

    public function getAll(): array
    {
        return $this->repo->findAll();
    }

    public function getUsersOfTeamsite(Teamsite $teamsite): array
    {
        $uuids = [];
        foreach ($teamsite->getCategories() as $category) {
            foreach ($category->getEntries() as $entry) {
                $uuids[] = $entry->getUserUuid();
            }
        }
        $users = $this->userRepo->findById($uuids);
        $ids = array_map(fn (User $user) => $user->getUuid(), $users);

        return array_combine($ids, $users);
    }

    public function renderSite(Teamsite $teamsite): ?array
    {
        $users = $this->getUsersOfTeamsite($teamsite);
        $result = [];
        foreach ($teamsite->getCategories() as $category) {
            $cat_array = [
                self::ARRAY_TITLE => $category->getTitle(),
                self::ARRAY_DESCRIPTION => $category->getDescription(),
                self::ARRAY_HIDEEMAIL => $category->getHideEmail(),
                self::ARRAY_HIDENAME => $category->getHideName(),
                self::ARRAY_ENTRIES => [],
            ];
            foreach ($category->getEntries() as $entry) {
                if (!isset($users[$entry->getUserUuid()->toString()])) {
                    continue;
                }
                $cat_array[self::ARRAY_ENTRIES][] = [
                    self::ARRAY_TITLE => $entry->getTitle(),
                    self::ARRAY_DESCRIPTION => $entry->getDescription(),
                    self::ARRAY_DISPLAYEMAIL => $entry->getDisplayEmail(),
                    self::ARRAY_USER => $this->userService->user2Array($users[$entry->getUserUuid()->toString()]),
                ];
            }
            $result[] = $cat_array;
        }

        return $result;
    }

    private static function checkEntry(array $a): bool
    {
        if (!is_array($a)) {
            return false;
        }
        foreach (self::ARRAY_ENTRY as $item) {
            if (!array_key_exists($item, $a)) {
                return false;
            }
        }
        if (!((is_array($a[self::ARRAY_USER]) && !is_null(UserService::array2Uuid($a[self::ARRAY_USER])))
            && is_string($a[self::ARRAY_TITLE])
            && is_string($a[self::ARRAY_DESCRIPTION])
        )) {
            return false;
        }

        return true;
    }

    private static function checkCategory(array $a): bool
    {
        if (!is_array($a)) {
            return false;
        }
        foreach (self::ARRAY_CATEGORY as $item) {
            if (!array_key_exists($item, $a)) {
                return false;
            }
        }
        if (!(is_array($a[self::ARRAY_ENTRIES])
            && is_string($a[self::ARRAY_TITLE])
            && is_string($a[self::ARRAY_DESCRIPTION])
            && is_bool($a[self::ARRAY_HIDEEMAIL])
            && is_bool($a[self::ARRAY_HIDENAME])
        )) {
            return false;
        }
        foreach ($a[self::ARRAY_ENTRIES] as $entry) {
            if (!self::checkEntry($entry)) {
                return false;
            }
        }

        return true;
    }

    private static function check(array $a): bool
    {
        foreach ($a as $category) {
            if (!self::checkCategory($category)) {
                return false;
            }
        }

        return true;
    }

    private function parse(array $parse, array &$result = []): bool
    {
        $uuids = [];
        $cnt = 1;
        foreach ($parse as $item) {
            $cat = (new TeamsiteCategory())
                ->setTitle($item[self::ARRAY_TITLE])
                ->setDescription($item[self::ARRAY_DESCRIPTION])
                ->setHideEmail($item[self::ARRAY_HIDEEMAIL])
                ->setHideName($item[self::ARRAY_HIDENAME])
                ->setOrd($cnt++);
            $cnt_i = 1;
            foreach ($item[self::ARRAY_ENTRIES] as $entry) {
                $uuid = UserService::array2Uuid($entry[self::ARRAY_USER]);
                $cat->addEntry((new TeamsiteEntry())
                    ->setTitle($entry[self::ARRAY_TITLE])
                    ->setDescription($entry[self::ARRAY_DESCRIPTION])
                    ->setDisplayEmail($entry[self::ARRAY_DISPLAYEMAIL])
                    ->setUserUuid($uuid)
                    ->setOrd($cnt_i++));
                $uuids[] = $uuid;
            }
            $result[] = $cat;
        }

        if ($users = $this->userRepo->findById($uuids)) {
            if (array_search(null, $users)) {
                return false;
            }
        }

        return true;
    }

    public function parseSite(Teamsite $teamsite, ?array $input): bool
    {
        $result = [];

        if (is_null($input)) {
            return false;
        }
        if (!self::check($input)) {
            return false;
        }
        if (!$this->parse($input, $result)) {
            return false;
        }

        $this->em->beginTransaction();
        $teamsite->clearCategories();
        $this->em->persist($teamsite);
        $this->em->flush();

        foreach ($result as $category) {
            $teamsite->addCategory($category);
        }
        $this->em->persist($teamsite);
        $this->em->flush();
        $this->em->commit();

        return true;
    }

    public function wipe(WipeMode $mode): void
    {
        foreach ($this->repo->findAll() as $ts) {
            $this->em->remove($ts);
        }
        $this->em->flush();
    }

    public function wipeBefore(WipeMode $mode): array
    {
        return [NavigationService::class];
    }
}
