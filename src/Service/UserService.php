<?php

namespace App\Service;

use App\Idm\IdmManager;
use App\Idm\IdmRepository;
use App\Model\ClanModel;
use App\Security\User;
use App\Security\UserInfo;
use App\Transfer\ClanCreateTransfer;
use App\Transfer\PaginationCollection;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;

final class UserService
{
    private IdmManager $im;
    private IdmRepository $userRepo;
    private IdmRepository $clanRepo;
    private LoggerInterface $logger;

    public function __construct(
        LoggerInterface $logger,
        IdmManager $im
    ) {
        $this->im = $im;
        $this->logger = $logger;
    }

    /**
     * @param string $email
     * @param string $password
     * @return bool true when authenticated successfully, false otherwise
     */
    public function authenticate(string $email, string $password): bool
    {

    }

    /**
     * Requests a full Clan object from IDM, only to be used if up-to-date data is required (e.g. for admin purpose).
     *
     * @param string $clanuuid UUID of the Clan to search for
     * @param bool   $inactive also retrieves Clans that are either inactive or have no Users attached to them
     *
     * @return ClanModel|null the Clan object, if it exits, null otherwise
     */
    public function getClan(string $clanuuid, bool $inactive = false): ?ClanModel
    {

    }

    /**
     * Edits a Clan in the IDM.
     *
     * @return bool true if the Edit was successful, otherwise return false
     */
    public function editClan(ClanModel $clan): bool
    {

    }

    /**
     * Creates a Clan in the IDM.
     *
     * @return ClanModel|bool returns the created Clan when successful, otherwise false
     */
    public function createClan(ClanCreateTransfer $clan, string $adminuuid = null): ?ClanModel
    {

    }

    /**
     * Adds Users to a Clan in IDM.
     *
     * @param ClanModel   $clan
     * @param User[]      $users
     * @param string|null $joinPassword
     *
     * @return bool True if the UserAdd was successful, otherwise return false
     */
    public function addClanMember(ClanModel $clan, array $users, string $joinPassword = null): bool
    {

    }

    /**
     * Removes Users from a Clan in IDM.
     *
     * @param User[] $users
     * @param bool   $strictmode for non-Admin Requests, so you cannot remove the last Owner
     *
     * @return bool True if the UserRemove was successful, otherwise return false
     */
    public function removeClanMember(ClanModel $clan, array $users, bool $strictmode = true): bool
    {

    }

    /**
     * Deletes a Clan in the IDM.
     *
     * @return bool true if the Delete was successful, otherwise return false
     */
    public function deleteClan(ClanModel $clan): bool
    {

    }

    /**
     * Requests a full user object from IDM, only to be used if up-to-date data is required (e.g. for admin purpose).
     *
     * @param string $username either email address of uuid of the user to search for
     *
     * @return User|null the user object, if it exits, null otherwise
     */
    public function getUser(string $username): ?User
    {

    }

    /**
     * Requests User Objects from IDM, only to be used if up-to-date data is required (e.g. for admin purpose).
     *
     * @param string|null $query if no Query is supplied, all Users are returned
     * @param int         $page  the Page to be requested (default Page 1)
     * @param int|null    $limit the Items per Page (Defaults to 10 Items)
     *
     * @return PaginationCollection|bool the user object Collection, if it exits, null otherwise
     */
    public function queryUsers(string $query = null, int $page = null, int $limit = null): ?PaginationCollection
    {

    }

    /**
     * Requests Clans Objects from IDM, only to be used if up-to-date data is required (e.g. for admin purpose).
     *
     * @param string|null $query    if no Query is supplied, all Users are returned
     * @param int         $page     the Page to be requested (default Page 1)
     * @param int|null    $limit    the Items per Page (Defaults to 10 Items)
     * @param bool        $fullInfo If yes also pulls the User Relations, otherwise only the basic ClanModel is available
     *
     * @return PaginationCollection|bool the clan object Collection, if it exits, null otherwise
     */
    public function queryClans(string $query = null, int $page = null, int $limit = null, bool $fullInfo = false): ?PaginationCollection
    {

    }

    /**
     * Returns all users that match a set of uuids. This function makes an IDM access, only to be used if up-to-date data is required (e.g. for admin purpose).
     * @param array $uuids Ids to get user for.
     * @param bool $assoc Returns an associative array "uuid => User"
     * @return User[] Array of users.
     */
    public function getUsersByUuid(array $uuids, bool $assoc = false) : ?array
    {

    }

    /**
     * Registers a User in the IDM.
     *
     * @param array $userdata see UserRegisterType for Fields
     *
     * @return bool true if the Registration was successful, otherwise return false
     */
    public function registerUser(array $userdata): bool
    {

    }

    /**
     * Edits a User in the IDM.
     *
     * @return bool true if the Edit was successful, otherwise return false
     */
    public function editUser(User $user): bool
    {
        // TODO: Throw an Exception when there was a ValidationError ServerSide and show the fancy Error in the Frontend

    }

    /**
     * Checks if a Nickname or an EMail is already used.
     *
     * @param string $data the Nickname/EMail to be checked against the IDM
     *
     * @return bool true if the Nickname/EMail is NOT used already
     */
    public function checkUserAvailability($data): bool
    {

    }

    /**
     * Checks if a Clanname or an Clantag is already used.
     *
     * @param string $data the Name/Tag to be checked against the IDM
     * @param string $mode The Mode if its a Name or a Tag (clantag/clanname)
     *
     * @return bool true if the Name/Tag is NOT used already
     */
    public function checkClanAvailability(string $data, string $mode): bool
    {

    }

    /**
     * @param $uuid Uuid Id to get user info for.
     * @return UserInfo|null The requested user info
     */
    public function getUserInfoByUuid($uuid) : ?UserInfo
    {

    }
}
