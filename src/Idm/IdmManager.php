<?php

namespace App\Idm;

use App\Entity\Clan;
use App\Entity\User;
use App\Idm\Annotation\Collection;
use App\Idm\Annotation\Entity;
use App\Idm\Exception\NotAttachedException;
use App\Idm\Exception\UnsupportedClassException;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\Reader;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Class IdmManager
 * @package App\Idm
 *
 * Some limitations:
 *   - The objects must not contain references to other objects
 *   - The objects may contain Collections LazyLoadCollections
 */
final class IdmManager
{
    private LoggerInterface $logger;
    private HttpClientInterface $httpClient;
    private SerializerInterface $serializer;
    private IdmRepositoryFactory $repoFactory;
    private Reader $annotationReader;

    private array $paths;
    private UnitOfWork $unitOfWork;

    private const REST_FORMAT = 'json';
    private const URL_PREFIX = '/api';

    // Name of HttpClientInterface $idmClient is important to get idm.client injected by symfony
    public function __construct(HttpClientInterface $idmClient, SerializerInterface $serializer, LoggerInterface $logger, IdmRepositoryFactory $repoFactory, Reader $annotationReader)
    {
        $this->httpClient = $idmClient;
        $this->logger = $logger;
        $this->serializer = $serializer;
        $this->repoFactory = $repoFactory;
        $this->annotationReader = $annotationReader;

        $this->unitOfWork = new UnitOfWork($this);
        $this->paths = [];
    }

    public function isManaged($objectOrClass)
    {
        try {
            $reflectionClass = new ReflectionClass($objectOrClass);
        } catch (\ReflectionException $e) {
            return false;
        }
        if (array_key_exists($reflectionClass->getName(), $this->paths)) {
            return true;
        }
        $ano = $this->annotationReader->getClassAnnotation($reflectionClass, Entity::class);
        if ($ano) {
            $this->paths[$reflectionClass->getName()] = $ano->getPath();
            return true;
        }
        return false;
    }

    private function throwOnNotManaged(string $class)
    {
        if (!$this->isManaged($class))
            throw new UnsupportedClassException();
    }

    public function getRepository(string $class)
    {
        $this->throwOnNotManaged($class);

        return $this->repoFactory->getRepository($this, $class);
    }

    private function pathByClass(string $class)
    {
        // this checks registers the path in $this->paths
        $this->throwOnNotManaged($class);

        return $this->paths[$class];
    }

    private function createUrl(string $class, ?string $id = null): string
    {
        $url = self::URL_PREFIX . $this->pathByClass($class);
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
    private static function UuidObject2Uuid(array $a)
    {
        return array_map(function (array $a) {
            return $a['uuid'];
        }, $a);
    }

    private function hydrateObject($result, string $class)
    {
        $obj = $this->serializer->deserialize($result, $class, self::REST_FORMAT);

        $reflection = new ReflectionClass($obj);
        foreach ($reflection->getProperties() as $property) {
            $ano = $this->annotationReader->getPropertyAnnotation($property, Collection::class);
            if (!$ano) {
                continue;
            }
            $property->setAccessible(true);
            $property->setValue($obj, new LazyLoaderCollection($this, $ano->getClass(), self::UuidObject2Uuid($property->getValue($obj))));
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
        $this->throwOnNotManaged($class);

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

    }

    public function persist(object $object)
    {
        $this->throwOnNotManaged($object);

        if (!$this->unitOfWork->isAttached($object)) {
            $this->unitOfWork->register($object);
        }

        if (!$this->unitOfWork->isDirty($object)) {

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