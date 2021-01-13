<?php

namespace App\Idm;

use App\Idm\Annotation\Collection;
use App\Idm\Annotation\Entity;
use App\Idm\Exception\PersistException;
use App\Idm\Exception\UnsupportedClassException;
use App\Idm\Serializer\PaginationCollectionDenormalizer;
use App\Idm\Serializer\UuidNormalizer;
use Doctrine\Common\Annotations\Reader;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
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
    private IdmRepositoryFactory $repoFactory;
    private Reader $annotationReader;
    private Serializer $serializer;

    private UnitOfWork $unitOfWork;
    private array $paths;

    private const REST_FORMAT = 'json';
    private const URL_PREFIX = '/api';

    // Name of HttpClientInterface $idmClient is important to get idm.client injected by symfony
    public function __construct(HttpClientInterface $idmClient, LoggerInterface $logger, IdmRepositoryFactory $repoFactory, Reader $annotationReader)
    {
        $this->httpClient = $idmClient;
        $this->logger = $logger;
        $this->repoFactory = $repoFactory;
        $this->annotationReader = $annotationReader;

        $on = new ObjectNormalizer(null, null, null, new ReflectionExtractor());
        $this->serializer = new Serializer([
            new DateTimeNormalizer(),
            new UuidNormalizer(),
            new PaginationCollectionDenormalizer($on),
            $on
        ], [new JsonEncoder()]);
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

    private function throwOnNotManaged($objectOrClass)
    {
        if (!$this->isManaged($objectOrClass))
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

    private function createUrl($classOrObject, ?string $id = null): string
    {
        if (is_object($classOrObject))
            $class = get_class($classOrObject);
        else
            $class = $classOrObject;
        $url = self::URL_PREFIX . $this->pathByClass($class);
        if (!empty($id))
            $url .= '/' . $id;
        return $url;
    }

    /**
     * @param string $method HTTP Method to call (e.g. POST, GET, etc.)
     * @param string $url The url to call
     * @param array $response The response of the server is written at this reference.
     * @param array $expectedErrorCodes If an expected error code occurs, no error log is performed and $response is not set.
     * @param array|null $json_payload The payload to send (for POST, PATCH)
     * @return int The status code of the request
     */
    private function send(string $method, string $url, array &$response, array $expectedErrorCodes = [], array $json_payload = null)
    {
        // do a cache lookup first?
        try{
            $resp = $this->httpClient->request($method, $url, ['json' => $json_payload]);
            if (!in_array($resp->getStatusCode(), $expectedErrorCodes)) {
                $response = $resp->toArray();
            }
            return $resp->getStatusCode();
        } catch (ClientExceptionInterface $e) {
            // 4xx return code
            $this->logger->error('Invalid request to IDM ('.$e->getMessage().')');
        } catch (ServerExceptionInterface | RedirectionExceptionInterface | DecodingExceptionInterface $e) {
            // invalid content, 5xx, or too many 3xx
            $this->logger->error('IDM behaving incorrect ('.$e->getMessage().')');
        } catch (TransportExceptionInterface $e) {
            // network issue
            $this->logger->error('Connection to IDM failed ('.$e->getMessage().')');
        }
        return false;
    }

    private function throwOnCode(int $code, object $object = null)
    {
        // we only take care about 4xx codes
        if (intdiv($code, 100) != 4)
            return;

        switch ($code) {
            case Response::HTTP_BAD_REQUEST:
                throw new PersistException($object, PersistException::REASON_INVALID);
            case Response::HTTP_CONFLICT:
                throw new PersistException($object, PersistException::REASON_NON_UNIQUE);
            case Response::HTTP_NOT_FOUND:
                throw new PersistException($object, PersistException::REASON_NOT_FOUND);
            default:
                throw new PersistException($object, PersistException::REASON_UNKNOWN);
        }
    }

    private function get(string $url)
    {
        $response = [];
        $this->send('GET', $url, $response, [ Response::HTTP_NOT_FOUND ]);
        return $response;
    }

    private function post(string $url, object $object)
    {
        $response = [];
        $data = $this->object2Array($object);
        $code = $this->send('POST', $url, $response, [ Response::HTTP_CONFLICT ], $data);
        $this->throwOnCode($code, $object);
        return $response;
    }

    private function patch(string $url, object $object)
    {
        $response = [];
        $data = $this->object2Array($object);
        $code = $this->send('PATCH', $url, $response, [], $data);
        $this->throwOnCode($code, $object);
        return $response;
    }

    private function delete(string $url)
    {
        $response = [];
        $code = $this->send('DELETE', $url, $response, [ Response::HTTP_NOT_FOUND ]);
        $this->throwOnCode($code);
    }

    private function object2Array(object $object)
    {
        // TODO check if object is valid, best to do at persist?!?
        return $this->serializer->normalize($object, self::REST_FORMAT, [
            ObjectNormalizer::GROUPS => 'write',
            ObjectNormalizer::SKIP_NULL_VALUES => true,
        ]);
    }

    private function object2Id(object $object)
    {
        // TODO change getUuid with id annotation
        return $object->getUuid();
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

    private function hydrateObject($result, &$objectOrClass)
    {
        $reflection = new ReflectionClass($objectOrClass);

        $options = is_object($objectOrClass) ? [AbstractNormalizer::OBJECT_TO_POPULATE => &$objectOrClass] : [];
        $obj = $this->serializer->denormalize($result, $reflection->getName(), self::REST_FORMAT, $options);

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

        $result = $this->get($this->createUrl($class, $id));

        if (empty($result))
            return null;

        $obj = $this->hydrateObject($result, $class);
        $this->unitOfWork->register($obj, true);
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
        } else {
            $this->unitOfWork->persist($object);
        }
    }

    public function remove(object $object)
    {
        $this->throwOnNotManaged($object);
        $this->unitOfWork->delete($object);
    }

    public function flush()
    {
        $new = $this->unitOfWork->getNewObjects();
        $mod = $this->unitOfWork->getModifiedObjects();
        $del = $this->unitOfWork->getDeletedObjects();

        foreach ($new as &$object) {
            $this->hydrateObject($this->post($this->createUrl($object), $object), $object);
            $this->unitOfWork->flush($object);
        }

        foreach ($mod as &$object) {
            $this->hydrateObject($this->patch($this->createUrl($object, $this->object2Id($object)), $object), $object);
            $this->unitOfWork->flush($object);
        }

        foreach ($del as &$object) {
            $this->delete($this->createUrl($object, $this->object2Id($object)));
            $this->unitOfWork->flush($object);
        }
    }
}