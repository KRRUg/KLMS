<?php

namespace App\Idm;

use App\Idm\Annotation\Collection;
use App\Idm\Annotation\Entity;
use App\Idm\Annotation\Reference;
use App\Idm\Exception\PersistException;
use App\Idm\Exception\UnsupportedClassException;
use App\Idm\Serializer\LazyLoaderCollectionNormalizer;
use App\Idm\Serializer\PaginationCollectionDenormalizer;
use App\Idm\Serializer\UuidNormalizer;
use App\Idm\Transfer\AuthObject;
use App\Idm\Transfer\PaginationCollection;
use App\Idm\Transfer\UuidObject;
use Closure;
use Doctrine\Common\Annotations\Reader;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use ReflectionClass;
use ReflectionException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Intl\Exception\NotImplementedException;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Mapping\Loader\AnnotationLoader;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
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

    /**
     * @var Entity[]
     */
    private array $config;
    /**
     * @var ReflectionClass[]
     */
    private array $ref_cache;
    /**
     * @var UnitOfWork
     */
    private UnitOfWork $unitOfWork;

    private const REST_FORMAT = 'json';
    private const URL_PREFIX = '/api';

    // Name of HttpClientInterface $idmClient is important to get idm.client injected by symfony
    public function __construct(HttpClientInterface $idmClient, LoggerInterface $logger, IdmRepositoryFactory $repoFactory, Reader $annotationReader)
    {
        $this->httpClient = $idmClient;
        $this->logger = $logger;
        $this->repoFactory = $repoFactory;
        $this->annotationReader = $annotationReader;

        $on = new ObjectNormalizer(new ClassMetadataFactory(new AnnotationLoader($annotationReader)), null, null, new ReflectionExtractor());
        $this->serializer = new Serializer([
            new DateTimeNormalizer(),
            new UuidNormalizer(),
            new LazyLoaderCollectionNormalizer(),
            $on
        ], [new JsonEncoder()]);

        $this->unitOfWork = new UnitOfWork($this, $annotationReader);
        $this->config = [];
        $this->ref_cache = [];
    }

    public function isManaged($objectOrClass): bool
    {
        try {
            $reflectionClass = new ReflectionClass($objectOrClass);
        } catch (ReflectionException $e) {
            return false;
        }
        if (array_key_exists($reflectionClass->getName(), $this->config)) {
            return true;
        }
        $ano = $this->annotationReader->getClassAnnotation($reflectionClass, Entity::class);
        if ($ano) {
            $this->config[$reflectionClass->getName()] = $ano;
            $this->ref_cache[$reflectionClass->getName()] = $reflectionClass;
            return true;
        }
        return false;
    }

    private function throwOnNotManaged($objectOrClass)
    {
        if (!$this->isManaged($objectOrClass))
            throw new UnsupportedClassException();
    }

    public function getRepository(string $class): IdmRepository
    {
        $this->throwOnNotManaged($class);

        return $this->repoFactory->getRepository($this, $class);
    }

    private function pathByClass(string $class)
    {
        // this checks registers the path in $this->config
        $this->throwOnNotManaged($class);
        return $this->config[$class]->getPath();
    }

    private function hasAuthByClass(string $class)
    {
        // this checks registers the path in $this->config
        $this->throwOnNotManaged($class);
        return $this->config[$class]->hasAuthorize();
    }

    private function hasSearchByClass(string $class)
    {
        // this checks registers the path in $this->config
        $this->throwOnNotManaged($class);
        return $this->config[$class]->hasSearch();
    }

    private function createUrl($classOrObject, ?string $postfix = null): string
    {
        if (is_object($classOrObject))
            $class = get_class($classOrObject);
        else
            $class = $classOrObject;
        $url = self::URL_PREFIX . $this->pathByClass($class);
        if (!empty($postfix))
            $url .= '/' . $postfix;
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
    private function send(string $method, string $url, array &$response, array $expectedErrorCodes = [], array $query = [], array $json_payload = [])
    {
        // do a cache lookup first?
        try{
            $options = [];
            if (!empty($query))
                $options['query'] = $query;
            if (!empty($json_payload))
                $options['json'] = $json_payload;
            $resp = $this->httpClient->request($method, $url, $options);
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

    private function get(string $url, array $query = []): array
    {
        $response = [];
        $this->send('GET', $url, $response, [ Response::HTTP_NOT_FOUND ], $query);
        return $response;
    }

    private function post(string $url, object $object): array
    {
        $response = [];
        $data = $this->object2Array($object);
        $code = $this->send('POST', $url, $response, [ Response::HTTP_CONFLICT ], [], $data);
        $this->throwOnCode($code, $object);
        return $response;
    }

    private function patch(string $url, object $object): array
    {
        $response = [];
        $data = $this->object2Array($object);
        $code = $this->send('PATCH', $url, $response, [], [], $data);
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
        return $this->serializer->normalize($object, self::REST_FORMAT, [
            ObjectNormalizer::GROUPS => ['write'],
            ObjectNormalizer::SKIP_NULL_VALUES => true,
        ]);
    }

    public function object2Id(object $object)
    {
        // TODO change getUuid with id annotation
        return $object->getUuid();
    }

    private function mapAnnotation(object $object, Closure $closureReference, Closure $closureCollection)
    {
        $reflection = new ReflectionClass($object);

        foreach ($reflection->getProperties() as $property) {
            if($ano = $this->annotationReader->getPropertyAnnotation($property, Collection::class)) {
                $property->setAccessible(true);
                $property->setValue($object, $closureCollection($ano->getClass(), $property->getValue($object)));
            } elseif ($ano = $this->annotationReader->getPropertyAnnotation($property, Reference::class)) {
                $property->setAccessible(true);
                $property->setValue($object, $closureReference($ano->getClass(), $property->getValue($object)));
            }
        }
    }

    public function compareObjects(object $a, object $b): bool
    {
        if (get_class($a) != get_class($b))
            return false;

        $ref = new ReflectionClass($a);

        foreach ($ref->getProperties() as $property) {
            $property->setAccessible(true);
            $v_a = $property->getValue($a);
            $v_b = $property->getValue($b);

            if($ano = $this->annotationReader->getPropertyAnnotation($property, Collection::class)) {
                if(!LazyLoaderCollection::compare($v_a, $v_b))
                    return false;
            } elseif ($ano = $this->annotationReader->getPropertyAnnotation($property, Reference::class)) {
                if (!$this->compareObjects($v_a, $v_b))
                    return false;
            } else {
                if ($v_a != $v_b)
                    return false;
            }
        }
        return true;
    }

    private function hydrateObject($result, &$objectOrClass)
    {
        $options = is_object($objectOrClass) ? [AbstractNormalizer::OBJECT_TO_POPULATE => &$objectOrClass] : [];
        $class = is_object($objectOrClass) ? get_class($objectOrClass) : $objectOrClass;
        $obj = $this->serializer->denormalize($result, $class, self::REST_FORMAT, $options);

        $this->mapAnnotation($obj,
            function ($class, $obj){
                throw new NotImplementedException("@Reference annotation is not implemented in IdmManager yet");
            },
            function ($class, $list) {
                if ($list instanceof LazyLoaderCollection)
                    return $list;

                return LazyLoaderCollection::fromUuidList($this, $class, array_map(function (array $a) { return UuidObject::fromArray($a); }, $list));
            }
        );
        return $obj;
    }

    /**
     * Use this function for id search instead of filter!
     *
     * @param $class string The class to deserialize
     * @param $id string The URL of the object to request
     * @return object|null The requested object or null if not found
     */
    public function request(string $class, string $id): ?object
    {
        $this->throwOnNotManaged($class);

        if ($obj = $this->unitOfWork->get($class, $id)) {
            return $obj;
        }

        $result = $this->get($this->createUrl($class, $id));

        if (empty($result))
            return null;

        $obj = $this->hydrateObject($result, $class);
        $this->unitOfWork->register($obj, true);
        return $obj;
    }

    public function auth(string $class, string $name, string $secret): bool
    {
        if (!$this->hasAuthByClass($class))
            throw new UnsupportedClassException("Class {$class} does not support authentication.");

        $response = [];
        $code = $this->send('POST', $this->createUrl($class, 'authorize'), $response, [ Response::HTTP_NOT_FOUND ], [], $this->object2Array(new AuthObject($name, $secret)));
        return $code === Response::HTTP_OK;
    }

    public function search(string $class, array $parameter = [])
    {
        if (!$this->hasSearchByClass($class))
            throw new UnsupportedClassException("Class {$class} does not support search.");
        throw new NotImplementedException("Search support is not implemented yet");
    }

    public function find(string $class, array $filter = [], array $sort = [], ?int $page = 0, ?int $limit = null)
    {
        $this->throwOnNotManaged($class);

        $query = [];
        if (!empty($filter)) {
            $query['filter'] = $filter;
            $query['exact'] = 'true';
        }
        if (!empty($sort)) {
            $query['sort'] = $sort;
        }
        if (!empty($limit)) {
            $query['limit'] = $limit;
        }
        if (!empty($page)) {
            $query['page'] = $limit;
        }

        $result = $this->get($this->createUrl($class), $query);

        $collection = $this->serializer->denormalize($result, PaginationCollection::class, self::REST_FORMAT);

        if (empty($collection))
            throw new UnsupportedClassException('Invalid PaginationCollection returned');

        foreach ($collection->items as &$item) {
            $item = $this->hydrateObject($item, $class);
            if ($obj = $this->unitOfWork->get($class, $this->object2Id($item))) {
                $item = $obj;
            } else {
                $this->unitOfWork->register($item, true);
            }
        }
        return $collection;
    }

    private function fillProxyObjects(object &$object)
    {
        $this->mapAnnotation($object,
            function ($class, $obj){
                throw new NotImplementedException("@Reference annotation is not implemented in IdmManager yet");
            },
            function ($class, $list) {
                if ($list instanceof LazyLoaderCollection) {
                    foreach ($list->getLoadedItems() as $item) {
                        $this->fillProxyObjects($item);
                    }
                    return $list;
                } else {
                    $list = is_array($list) ? $list : [];
                    foreach ($list as &$item) {
                        $this->fillProxyObjects($item);
                    }
                    return LazyLoaderCollection::fromObjectList($this, $class, $list);
                }
            }
        );
    }

    public function persist(object &$object)
    {
        $this->throwOnNotManaged($object);

        $this->fillProxyObjects($object);

        $this->unitOfWork->persist($object);
    }

    public function remove(object $object)
    {
        $this->throwOnNotManaged($object);
        $this->unitOfWork->delete($object);
    }

    private function applyCollectionModification(object $object)
    {
        $modification = $this->unitOfWork->getCollectionDiff($object);
        $base_url = $this->createUrl($object, $this->object2Id($object));
        foreach ($modification as $name => $mod) {
            $url = $base_url . '/' . $name;
            foreach ($mod[0] as $added) {
                $this->post($url, new UuidObject($this->object2Id($added)));
            }
            foreach ($mod[1] as $removed) {
                $this->delete($url . '/' . $this->object2Id($removed));
            }
        }
    }

    public function flush()
    {
        foreach ($this->unitOfWork->getModifiedObjects() as $object) {
            switch ($this->unitOfWork->getObjectState($object)) {
                case UnitOfWork::STATE_DETACHED:
                case UnitOfWork::STATE_MANAGED:
                default:
                    // nothing to do
                    break;

                case UnitOfWork::STATE_CREATED:
                    $this->hydrateObject($this->post($this->createUrl($object), $object), $object);
                    break;

                case UnitOfWork::STATE_MODIFIED:
                    $this->applyCollectionModification($object);
                    $this->hydrateObject($this->patch($this->createUrl($object, $this->object2Id($object)), $object), $object);
                    break;

                case UnitOfWork::STATE_DELETE:
                    $this->delete($this->createUrl($object, $this->object2Id($object)));
                    break;
            }
            $this->unitOfWork->flush($object);
        }
    }
}