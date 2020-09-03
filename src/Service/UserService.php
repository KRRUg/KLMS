<?php


namespace App\Service;


use App\Exception\UserServiceException;
use App\Repository\UserAdminsRepository;
use App\Repository\UserGamerRepository;
use App\Security\User;
use App\Security\UserInfo;
use Doctrine\Common\Annotations\Annotation\Required;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
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
    ];

    private function getClient()
    {
        return HttpClient::create([
            'headers' => ['X-API-KEY' => $_ENV['KLMS_IDM_APIKEY']]
        ]);
    }

    private function getPath(string $endpoint, ?string $param = null)
    {
        $path = self::ENDPOINTS[$endpoint][self::PATH];
        if (empty($param)) {
            return "{$_ENV['KLMS_IDM_URL']}/api/{$path}";
        } else {
            return "{$_ENV['KLMS_IDM_URL']}/api/{$path}/{$param}";
        }
    }

    private function getMethod(string $endpoint)
    {
        return self::ENDPOINTS[$endpoint][self::METHOD];
    }

    /**
     * @param string $endpoint Endpoint identifier (see self::ENDPOINTS)
     * @param string|null $param REST url parameter
     * @param array $content The content of the request (will be encoded as JSON for the request)
     * @return bool|mixed Returns false on 404 or error, the data result otherwise
     *
     * @throws
     */
    private function request(string $endpoint, ?string $param = null, array $content = [])
    {
        try {
            $method = $this->getMethod($endpoint);
            $path = $this->getPath($endpoint, $param);
            $this->logger->debug("Sent {$method} request to {$path}");
            $response = $this->getClient()->request($method, $path,
                [
                    'json' => $content,
                ]
            );

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

    private function responseToUser(string $response) : User
    {
        $users = $this->responseToUsers("[".$response."]");
        if (count($users) != 1)
            throw new UserServiceException("Invalid response.");
        return $users[0];
    }

    private function responseToUsers(string $response) : array
    {
        $serializer = new Serializer([new ArrayDenormalizer(), new DateTimeNormalizer(), new ObjectNormalizer(null, null, null, new ReflectionExtractor() )], [new JsonEncoder()]);
        try{
            $user = $serializer->deserialize($response, User::class."[]", 'json');
        } catch (\RuntimeException $e) {
            throw new UserServiceException("Invalid response.", null, $e);
        }

        foreach ($user as $u) {
            $this->loadUserRoles($u);
            $this->loadAdminRoles($u);
        }
        return $user;
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
     * Returns all users that match a set of uuids. This function returns a cached UserInfo.
     * @param array $uuids Ids to get user for.
     * @return UserInfo[] Array of user infos.
     */
    public function getUserInfosByUuids(array $uuids) : array
    {
        // TODO make a cache lookup here
        return $this->getUsersByUuid($uuids);
    }

    /**
     * @param $uuid Id to get user info for.
     * @return UserInfo|null The requested user info
     */
    public function getUserInfoByUuid($uuid) : ?UserInfo
    {
        $users = $this->getUserInfosByUuids([$uuid]);
        if (empty($users))
            return null;
        return $users[0];
    }
}