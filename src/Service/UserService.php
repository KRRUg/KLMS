<?php


namespace App\Service;


use App\Exception\UserServiceException;
use App\Model\ClanModel;
use App\Model\UserClanModel;
use App\Repository\UserAdminsRepository;
use App\Repository\UserGamerRepository;
use App\Security\User;
use App\Security\UserInfo;
use App\Transfer\ClanMemberAdd;
use App\Transfer\ClanMemberRemove;
use App\Transfer\PaginationCollection;
use App\Transfer\ClanEditTransfer;
use App\Transfer\UserEditTransfer;
use Doctrine\Common\Annotations\Annotation\Required;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Ramsey\Uuid\Uuid;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class UserService
{
    private $logger;
    private $em;
    private $ar;
    private $gr;
    /**
     * @var int
     */
    private $statusCode;

    public function __construct(UserAdminsRepository $ar,
                                UserGamerRepository $gr,
                                EntityManagerInterface $em,
                                LoggerInterface $logger)
    {
        $this->ar = $ar;
        $this->gr = $gr;
        $this->em = $em;
        $this->logger = $logger;
    }

    const METHOD = 0;
    const PATH = 1;
    const ENDPOINTS = [
        'USER'  => [self::PATH => 'users',           self::METHOD => 'GET' ],
        'USERS' => [self::PATH => 'users/search',    self::METHOD => 'POST'],
        'AUTH'  => [self::PATH => 'users/authorize', self::METHOD => 'POST'],
        'REGISTER' => [self::PATH => 'users/register',    self::METHOD => 'POST'],
        'USEREDIT' => [self::PATH => 'users',    self::METHOD => 'PATCH'],
        'USERCHECK' => [self::PATH => 'users/check',    self::METHOD => 'POST'],
        'CLANCREATE' => [self::PATH => 'clans',    self::METHOD => 'POST'],
        'CLAN' => [self::PATH => 'clans',    self::METHOD => 'GET'],
        'CLANEDIT' => [self::PATH => 'clans',    self::METHOD => 'PATCH'],
        'CLANDELETE' => [self::PATH => 'clans',    self::METHOD => 'DELETE'],
        'CLANUSERADD' => [self::PATH => 'clans',    self::METHOD => 'PATCH'], // /clans/{uuid}/users
        'CLANUSERREMOVE' => [self::PATH => 'clans',    self::METHOD => 'DELETE'], // /clans/{uuid}/users
        'CLANCHECK' => [self::PATH => 'clans/check',    self::METHOD => 'POST'],

    ];

    const CLANRANKS = [
        'ADMIN',
        'MEMBER'
    ];

    static $serializer = null;

    private static function getSerializer()
    {
        if (empty(self::$serializer)) {
            self::$serializer = new Serializer([new ArrayDenormalizer(), new DateTimeNormalizer(), new ObjectNormalizer(null, null, null, new ReflectionExtractor() )], [new JsonEncoder()]);
        }

        return self::$serializer;
    }

    private function getClient()
    {
        return HttpClient::create([
            'headers' => ['X-API-KEY' => $_ENV['KLMS_IDM_APIKEY']]
        ]);
    }

    private function getPath(string $endpoint, ?string $slug = null)
    {
        $url = "{$_ENV['KLMS_IDM_URL']}/api/";
        $url = $url . self::ENDPOINTS[$endpoint][self::PATH];
        if (!empty($slug)) {
            $url = $url . "/{$slug}";
        }
        return $url;
    }

    private function getMethod(string $endpoint)
    {
        return self::ENDPOINTS[$endpoint][self::METHOD];
    }

    /**
     * @param string $endpoint Endpoint identifier (see self::ENDPOINTS)
     * @param string|null $slug REST url parameter
     * @param array|mixed $content The content of the request (will be encoded as JSON for the request)
     * @return bool|mixed Returns false on 404 or error, the data result otherwise
     *
     * @throws
     */
    private function request(string $endpoint, ?string $slug = null, $content = [])
    {
        try {
            $method = $this->getMethod($endpoint);
            $path = $this->getPath($endpoint, $slug);
            $this->logger->debug("Sent {$method} request to {$path}");
            $client = $this->getClient();

            if ($method === "GET") {
                $response = $client->request($method, $path,
                    [
                        'query' => $content,
                    ]
                );
            } else {
                $response = $client->request($method, $path,
                    [
                        'json' => $content,
                    ]
                );
            }

            $this->statusCode = $response->getStatusCode();

            if ($response->getStatusCode() === 404) {
                return false;
            }

            return $response->getContent();

        } catch (ClientExceptionInterface $e) {
            // 4xx return code (but 404 which is an expected)
            $this->logger->error('Invalid request to IDM (' . $e->getMessage() . ')');
        } catch (DecodingExceptionInterface | ServerExceptionInterface | RedirectionExceptionInterface $e) {
            // invalid content, 5xx, or too many 3xx
            $this->logger->error('IDM behaving incorrect (' . $e->getMessage() . ')');
        } catch (TransportExceptionInterface $e) {
            // network issue
            $this->logger->error('Connection to IDM failed (' . $e->getMessage() . ')');
        }

        throw new UserServiceException();
    }

    /**
     * @param string $email
     * @param string $password
     * @return bool true when authenticated successfully, false otherwise
     */
    public function authenticate(string $email, string $password) : bool
    {
        $result = $this->request ('AUTH', null, [
            'email' => $email,
            'password' => $password,
        ]);

        if ($result === false) {
            return false;
        } else {
            return true;
        }
    }

    private function loadAdminRoles(User $user)
    {
        $userGuid = $user->getUuid();
        $roles = [];

        // TODO extend admin table with roles
        if ($user->getIsSuperadmin() || $this->ar->find($userGuid)) {
            array_push($roles, "ROLE_ADMIN");
        }
        $user->addRoles($roles);
    }

    private function loadUserRoles(User $user)
    {
        $userGuid = $user->getUuid();
        $roles = [];

        $gamer = $this->gr->find($userGuid);
        if ($gamer) {
            if ($gamer->getPayed()) {
                array_push($roles, "ROLE_USER_PAYED");
            }
            // TODO check if user has seat,...
        }
        $user->addRoles($roles);
    }

    private function loadUserClans(User $user)
    {

        // TODO: deserialize with Symfony Serializer (nested Objects)
        $userclans = [];

        if(!empty($user->getClans())) {
            foreach ($user->getClans() as $k) {
                $clan = new ClanModel();
                $clan->setUuid(Uuid::fromString($k['clan']['uuid']));
                $clan->setName($k['clan']['name']);
                $clan->setClantag($k['clan']['clantag']);
                $clan->setWebsite($k['clan']['website']);
                $clan->setDescription($k['clan']['description']);

                $userclan = new UserClanModel();
                $userclan->setAdmin($k['admin']);
                $userclan->setClan($clan);

                $userclans[] = $userclan;
            }

            $user->setClans($userclans);
        }
    }

    private function loadClanUsers(ClanModel $clan) {
        // TODO: deserialize with Symfony Serializer (nested Objects)
        $userclans = [];

        if(!empty($clan->getUsers())) {
            foreach ($clan->getUsers() as $k) {
                $user = new User();
                $user->setEmail($k['user']['email']);
                $user->setNickname($k['user']['nickname']);
                $user->setStatus($k['user']['status']);
                $user->setUuid($k['user']['uuid']);
                $user->setId($k['user']['id']);

                $userclan = new UserClanModel();
                $userclan->setAdmin($k['admin']);
                $userclan->setUser($user);

                $userclans[] = $userclan;
            }
        }

        $clan->setUsers($userclans);
    }

    private function responseToUser(string $response) : User
    {
        $users = $this->responseToUsers("[".$response."]");
        if (count($users) != 1)
            throw new UserServiceException("Invalid response.");
        return $users[0];
    }

    private function responseToUsers(string $response) : array
    {
        $serializer = self::getSerializer();
        try{
            $user = $serializer->deserialize($response, User::class."[]", 'json');
        } catch (\RuntimeException $e) {
            throw new UserServiceException("Invalid response.", null, $e);
        }

        foreach ($user as $u) {
            $this->loadUserRoles($u);
            $this->loadAdminRoles($u);
            $this->loadUserClans($u);
        }
        return $user;
    }

    private function responseToPagedUsers(string $response)
    {
        $serializer = self::getSerializer();
        try{
            $ret = $serializer->deserialize($response, PaginationCollection::class, 'json');
            $ret->items = $serializer->denormalize($ret->items, User::class."[]");
        } catch (\RuntimeException | ExceptionInterface $e) {
            throw new UserServiceException("Invalid response.", null, $e);
        }
        return $ret;
    }

    private function responseToClan(string $response) : ClanModel
    {
        $clans = $this->responseToClans("[".$response."]");
        if (count($clans) != 1)
            throw new UserServiceException("Invalid response.");
        return $clans[0];
    }

    private function responseToClans(string $response) : array
    {
        $serializer = new Serializer([new ArrayDenormalizer(), new DateTimeNormalizer(), new ObjectNormalizer(null, null, null, new ReflectionExtractor() )], [new JsonEncoder()]);
        try{
            $clan = $serializer->deserialize($response, ClanModel::class."[]", 'json');
        } catch (\RuntimeException $e) {
            throw new UserServiceException("Invalid response.", null, $e);
        }

        foreach ($clan as $c) {
            $this->loadClanUsers($c);
        }
        return $clan;
    }

    private function requestToUserEdit(User $user) : UserEditTransfer
    {
        try{
            $data = UserEditTransfer::fromUser($user);
        } catch (\RuntimeException $e) {
            throw new UserServiceException("Invalid request.", null, $e);
        }

        return $data;
    }

    private function requestToClanEdit(ClanModel $clan) : ClanEditTransfer
    {
        try{
            $data = ClanEditTransfer::fromClan($clan);
        } catch (\RuntimeException $e) {
            throw new UserServiceException("Invalid request.", null, $e);
        }

        return $data;
    }

    /**
     * @param User[] $users
     * @param string|null $joinPassword
     * @return ClanMemberAdd
     */
    private function requestToClanMemberAdd(array $users, string $joinPassword = null) : ClanMemberAdd
    {
        try{
            $data = ClanMemberAdd::fromUsers($users, $joinPassword);
        } catch (\RuntimeException $e) {
            throw new UserServiceException("Invalid request.", null, $e);
        }

        return $data;
    }

    /**
     * @param User[] $users
     * @param bool $strictmode
     * @return ClanMemberRemove
     */
    private function requestToClanMemberRemove(array $users, bool $strictmode) : ClanMemberRemove
    {
        try{
            $data = ClanMemberRemove::fromUsers($users, $strictmode);
        } catch (\RuntimeException $e) {
            throw new UserServiceException("Invalid request.", null, $e);
        }

        return $data;
    }

    /**
     * Requests a full Clan object from IDM, only to be used if up-to-date data is required (e.g. for admin purpose).
     * @param string $clanuuid UUID of the Clan to search for.
     * @return ClanModel|null The Clan object, if it exits, null otherwise.
     */
    public function getClan(string $clanuuid) : ?ClanModel
    {
        $result = $this->request('CLAN', $clanuuid);
        if ($result === false) {
            return null;
        } else {
            return $this->responseToClan($result);
        }
    }

    /**
     * Requests all Clan Objects from IDM, only to be used if up-to-date data is required (e.g. for admin purpose).
     * @param integer $page the Page to be requested (1 Page = 50 Clans) WIP.
     * @return ClanModel[]|null The Clan object Collection, if it exits, null otherwise.
     */
    public function getAllClans(int $page = 1, bool $fullInfo = false) : ?array
    {
        // TODO: implement Pagination
        if($fullInfo) {
            $select = null;
        } else {
            $select = ['select' => 'list'];
        }
        $result = $this->request('CLAN', null, $select);
        if ($result === false) {
            return null;
        } else {
            return $this->responseToClans($result);
        }
    }

    /**
     * Edits a Clan in the IDM
     * @param ClanModel $clan
     * @return bool True if the Edit was successful, otherwise return false.
     */
    public function editClan(ClanModel $clan) : bool
    {
        // TODO: Throw an Exception when there was a ValidationError ServerSide and show the fancy Error in the Frontend
        $clandata = $this->requestToClanEdit($clan);
        $result = $this->request('CLANEDIT', $clan->getUuid(), $clandata);
        if ($result === false) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Adds Users to a Clan in IDM
     * @param ClanModel $clan
     * @param User[] $users
     * @return bool True if the UserAdd was successful, otherwise return false.
     */
    public function addClanMember(ClanModel $clan, array $users) : bool
    {
        // TODO: Throw an Exception when there was a ValidationError ServerSide and show the fancy Error in the Frontend
        $clandata = $this->requestToClanMemberAdd($users);
        $result = $this->request('CLANUSERADD', $clan->getUuid() . '/users', $clandata);
        if ($result === false) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Removes Users from a Clan in IDM
     * @param ClanModel $clan
     * @param User[] $users
     * @param bool $strictmode for non-Admin Requests, so you cannot remove the last Owner
     * @return bool True if the UserRemove was successful, otherwise return false.
     */
    public function removeClanMember(ClanModel $clan, array $users, bool $strictmode = true) : bool
    {
        // TODO: Throw an Exception when there was a ValidationError ServerSide and show the fancy Error in the Frontend
        $clandata = $this->requestToClanMemberRemove($users, $strictmode);
        dump($clandata);
        $result = $this->request('CLANUSERREMOVE', $clan->getUuid() . '/users', $clandata);
        if ($result === false) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Deletes a Clan in the IDM
     * @param ClanModel $clan
     * @return bool True if the Delete was successful, otherwise return false.
     */
    public function deleteClan(ClanModel $clan) : bool
    {
        // TODO: Throw an Exception when there was a ValidationError ServerSide and show the fancy Error in the Frontend
        $result = $this->request('CLANDELETE', $clan->getUuid());
        if ($result === false) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Requests a full user object from IDM, only to be used if up-to-date data is required (e.g. for admin purpose).
     * @param string $username Either email address of uuid of the user to search for.
     * @return User|null The user object, if it exits, null otherwise.
     */
    public function getUser(string $username) : ?User
    {
        $result = $this->request('USER', $username);
        if ($result === false) {
            return null;
        } else {
            return $this->responseToUser($result);
        }
    }

    /**
     * Requests all User Objects from IDM, only to be used if up-to-date data is required (e.g. for admin purpose).
     * @param integer $page the Page to be requested (1 Page = 50 Users) WIP.
     * @return User[]|null The user object Collection, if it exits, null otherwise.
     */
    public function queryUsers(string $query = null, int $page = null, int $limit = null)
    {
        $q = array();
        if (!empty($query))
            $q['q'] = $query;
        if (!empty($page))
            $q['page'] = $page;
        if (!empty($limit))
            $q['limit'] = $limit;

        $response = $this->request('USER', null, $q);
        if (!$response)
            return false;
        return $this->responseToPagedUsers($response);
    }

    /**
     * Returns all users that match a set of uuids. This function makes an IDM access, only to be used if up-to-date data is required (e.g. for admin purpose).
     * @param array $uuids Ids to get user for.
     * @return User[] Array of users.
     */
    public function getUsersByUuid(array $uuids) : ?array
    {
        if (empty($uuids))
            return [];

        $result = $this->request('USERS', null, ["uuid" => $uuids]);
        if ($result === false) {
            return null;
        } else {
            return  $this->responseToUsers($result);
        }
    }

    /**
     * Registers a User in the IDM
     * @param array $userdata See UserRegisterType for Fields.
     * @return bool True if the Registration was successful, otherwise return false.
     */
    public function registerUser(array $userdata) : bool
    {
        $result = $this->request('REGISTER', null, $userdata);
        if ($result === false) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Edits a User in the IDM
     * @param User $user
     * @return bool True if the Edit was successful, otherwise return false.
     */
    public function editUser(User $user) : bool
    {
        // TODO: Throw an Exception when there was a ValidationError ServerSide and show the fancy Error in the Frontend
        $userdata = $this->requestToUserEdit($user);
        $result = $this->request('USEREDIT', $user->getUuid(), $userdata);
        if ($result === false) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Checks if a Nickname or an EMail is already used.
     * @param string $data The Nickname/EMail to be checked against the IDM.
     * @return bool true if the Nickname/EMail is NOT used already.
     */
    public function checkUserAvailability($data) : bool
    {
        if(false != filter_var($data, FILTER_VALIDATE_EMAIL)) {
            $mode = 'email';
        } else {
            $mode = 'nickname';
        }
        $result = $this->request('USERCHECK', null, ['mode' => $mode, 'name' => $data] );
        if ($this->statusCode === RESPONSE::HTTP_NOT_FOUND) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Checks if a Clanname or an Clantag is already used.
     * @param string $data The Name/Tag to be checked against the IDM.
     * @param string $mode The Mode if its a Name or a Tag (clantag/clanname)
     * @return bool true if the Name/Tag is NOT used already.
     */
    public function checkClanAvailability(string $data, string $mode) : bool
    {
        $result = $this->request('CLANCHECK', null, ['mode' => $mode, 'name' => $data] );
        if ($this->statusCode === RESPONSE::HTTP_NOT_FOUND) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Returns all users that match a set of uuids. This function returns a cached UserInfo.
     * @param array $uuids Ids to get user for.
     * @return UserInfo[] Array of user infos.
     */
    public function getUsersInfoByUuid(array $uuids) : array
    {
        // TODO make a cache lookup here
        return $this->getUsersByUuid($uuids);
    }

    /**
     * Returns all users. This function returns a cached UserInfo.
     * @return UserInfo[] Array of user infos.
     */
    public function getAllUsersInfoByUuid() : array
    {
        // TODO make a cache lookup here
        return $this->getAllUsersInfoByUuid();
    }
}