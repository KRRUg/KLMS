<?php


namespace App\Service;


use App\Security\User;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

class IdmService
{
    private $logger;
    private $em;

    public function __construct(EntityManagerInterface $em, LoggerInterface $logger)
    {
        $this->em = $em;
        $this->logger = $logger;
    }

    const METHOD = 0;
    const PATH = 1;
    const ENDPOINTS = [
        'USERS' => [self::PATH => 'users',           self::METHOD => 'GET' ],
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
            return $response->toArray()['data'];

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

        // TODO Decide whether to throw error in such cases.
        return false;
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
            return $result['email'] === $email;
        }
    }

    /**
     * @param string $username
     * @return User|null
     */
    public function getUser(string $username) : ?User
    {
        $result = $this->request('USERS', $username);

        if ($result === false) {
            return null;
        } else {
            $user = new User();
            $user->setUsername($result[0]['email']);
            $user->setUuid($result[0]['uuid']);
            $user->setClans([]);
            return $user;
        }
    }
}