<?php

namespace App\Idm;

use App\Entity\Clan;
use App\Entity\User;
use App\Idm\Exception\NotAttachedException;
use App\Idm\Exception\UnsupportedClassException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class IdmManager
{
    private LoggerInterface $logger;
    private HttpClientInterface $httpClient;
    private SerializerInterface $serializer;
    private IdmRepositoryFactory $repoFactory;

    private UnitOfWork $unitOfWork;

    private const REST_FORMAT = 'json';
    private const URL_PREFIX = '/api';

    // Name of HttpClientInterface $idmClient is important to get idm.client injected by symfony
    public function __construct(HttpClientInterface $idmClient, SerializerInterface $serializer, LoggerInterface $logger, IdmRepositoryFactory $repoFactory)
    {
        $this->httpClient = $idmClient;
        $this->logger = $logger;
        $this->serializer = $serializer;
        $this->repoFactory = $repoFactory;
        $this->unitOfWork = new UnitOfWork($this);
    }

    public static function isClassManaged(string $class)
    {
        return $class ===  Clan::class || $class === User::class;
    }

    public static function isObjectManaged(object $object)
    {
        return $object instanceof Clan || $object instanceof User;
    }

    private static function throwOnInvalidClass(string $class)
    {
        if (!self::isClassManaged($class))
            throw new UnsupportedClassException();
    }

    private static function throwOnInvalidObject(object $object)
    {
        if (!self::isObjectManaged($object))
            throw new UnsupportedClassException();
    }

    public function getRepository(string $class)
    {
        self::throwOnInvalidClass($class);

        return $this->repoFactory->getRepository($this, $class);
    }

    private function pathByClass(string $class)
    {
        switch ($class) {
            case Clan::class:
                return 'clans';
            case User::class:
                return 'users';
            default:
                throw new \InvalidArgumentException();
        }
    }

    private function createUrl(string $class, ?string $id = null): string
    {
        $url = self::URL_PREFIX . '/' . $this->pathByClass($class);
        if (!empty($id))
            $url .= '/' . $id;
        return $url;
    }

    private function get(string $url, array $expectedErrorCodes = [])
    {
        // do a cache lookup first
        try{
            $response = $this->httpClient->request('GET', $url);
            if (in_array($response->getStatusCode(), $expectedErrorCodes)) {
                return false;
            }
            return $response->getContent();
        } catch (ClientExceptionInterface $e) {
            // 4xx return code
            $this->logger->error('Invalid request to IDM ('.$e->getMessage().')');
        } catch (ServerExceptionInterface | RedirectionExceptionInterface $e) {
            // invalid content, 5xx, or too many 3xx
            $this->logger->error('IDM behaving incorrect ('.$e->getMessage().')');
        } catch (TransportExceptionInterface $e) {
            // network issue
            $this->logger->error('Connection to IDM failed ('.$e->getMessage().')');
        }
        return false;
    }

    /**
     * @param array $a Array of associative array with uuid key
     * @return array array of uuids
     */
    private static function UuidObjectUuid(array $a)
    {
        return array_map(function (array $a) {
            return $a['uuid'];
        }, $a);
    }

    private function hydrateObject($result, string $class)
    {
        $obj = $this->serializer->deserialize($result, $class, self::REST_FORMAT);

        // TODO this can be replaced with @IdmLazyLoad(class='...') in the classes
        switch ($class) {
            case Clan::class:
                $obj->setUsers(new LazyLoaderCollection($this, User::class, self::UuidObjectUuid($obj->getUsers())));
                $obj->setAdmins(new LazyLoaderCollection($this, User::class, self::UuidObjectUuid($obj->getAdmins())));
                break;
            case User::class:
                $obj->setClans(new LazyLoaderCollection($this, Clan::class, self::UuidObjectUuid($obj->getClans())));
                break;
            default:
                break;
        }

        return $obj;
    }

    /**
     * @param $class string The class to deserialize
     * @param $id string The URL of the object to request
     * @return object|null The requested object or null if not found
     */
    public function request(string $class, string $id)
    {
        self::throwOnInvalidClass($class);

        if ($obj = $this->unitOfWork->get($id)) {
            return $obj;
        }

        $result = $this->get($this->createUrl($class, $id), [ 404 ]);

        if (empty($result))
            return null;

        $obj = $this->hydrateObject($result, $class);
        $this->unitOfWork->register($obj);
        return $obj;
    }

    public function find(string $class, array $parameter = [])
    {
        // 1. do cache lookup or IDM request
        // 2. check with unit of work
    }

    public function persist(object $object)
    {
        self::throwOnInvalidObject($object);

        $id = $object->getUuid();
        if (!$this->unitOfWork->isAttached($id)) {
            throw new NotAttachedException();
        }

        if (!$this->unitOfWork->isDirty($id)) {
            // TODO check items
            return;
        }
    }

    public function remove(object $object)
    {

    }

    public function flush()
    {

    }

    public function close()
    {
        // delete unit of work
        // check if manager is closed at every other option
    }
}