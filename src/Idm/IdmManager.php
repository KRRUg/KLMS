<?php

namespace App\Idm;

use App\Idm\Annotation\Collection;
use App\Idm\Annotation\Entity;
use App\Idm\Annotation\Reference;
use App\Idm\Exception\NotImplementedException;
use App\Idm\Exception\PersistException;
use App\Idm\Exception\UnsupportedClassException;
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
            $on
        ], [new JsonEncoder()]);

        $this->config = [];
        $this->ref_cache = [];

        $this->reset();
    }

    public function reset()
    {
        $this->unitOfWork = new UnitOfWork($this, $this->annotationReader);
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

    private function getFieldsByAnnotation(string $class, string $annotationClass): array
    {
        $reflection = $this->ref_cache[$class];
        $result = [];
        foreach ($reflection->getProperties() as $property) {
            if($ano = $this->annotationReader->getPropertyAnnotation($property, $annotationClass)) {
                $result[$property->getName()] = $ano;
            }
        }
        return $result;
    }

    private static array $attribute_cache_collection = [];
    private static array $attribute_cache_reference = [];

    public function getReferenceFields($objectOrClass): array
    {
        $this->throwOnNotManaged($objectOrClass);

        $class = is_object($objectOrClass) ? get_class($objectOrClass) : $objectOrClass;

        if (isset(self::$attribute_cache_reference[$class]))
            return self::$attribute_cache_reference[$class];

        return self::$attribute_cache_reference[$class] = $this->getFieldsByAnnotation($class, Reference::class);
    }

    public function getCollectionFields($objectOrClass): array
    {
        $this->throwOnNotManaged($objectOrClass);

        $class = is_object($objectOrClass) ? get_class($objectOrClass) : $objectOrClass;

        if (isset(self::$attribute_cache_collection[$class]))
            return self::$attribute_cache_collection[$class];

        return self::$attribute_cache_collection[$class] = $this->getFieldsByAnnotation($class, Collection::class);
    }

    private function pathByClass(string $class): string
    {
        // this checks registers the path in $this->config
        $this->throwOnNotManaged($class);
        return $this->config[$class]->getPath();
    }

    private function hasAuthByClass(string $class): bool
    {
        // this checks registers the path in $this->config
        $this->throwOnNotManaged($class);
        return $this->config[$class]->hasAuthorize();
    }

    private function hasSearchByClass(string $class): bool
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
        $class = get_class($object);
        $reflection = $this->ref_cache[$class];

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

    private function compareCollections($a, $b): bool
    {
        $a = ($a instanceof LazyLoaderCollection) ? $a->toArray(false) : $a;
        $b = ($b instanceof LazyLoaderCollection) ? $b->toArray(false) : $b;

        if (!is_array($a) || !is_array($b))
            return false;
        if (sizeof($a) != sizeof($b))
            return false;

        $a = array_map(function ($i_a) { return $this->object2Id($i_a); }, $a);
        $b = array_map(function ($i_b) { return $this->object2Id($i_b); }, $b);
        return empty(array_diff($a, $b));
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
                if(!$this->compareCollections($v_a, $v_b))
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

    private static function toUuidObjectArray(array $array, bool $strict = false)
    {
        $result = [];
        foreach ($array as $item) {
            if(!($result[] = UuidObject::fromArray($item, $strict)))
                return null;
        }
        return $result;
    }

    private static function setPrivateField(object $object, string $field, $value)
    {
        $set = function() use ($field, $value) {
            $this->$field = $value;
        };
        $set->call($object);
    }

    private static function getPrivateField(object $object, string $field)
    {
        $set = function() use ($field) {
            return $this->$field;
        };
        return $set->call($object);
    }

    private function hydrateObject($result, &$objectOrClass)
    {
        $class = is_object($objectOrClass) ? get_class($objectOrClass) : $objectOrClass;
        $options = is_object($objectOrClass) ? [AbstractNormalizer::OBJECT_TO_POPULATE => &$objectOrClass] : [];

        $collectionFields = $this->getCollectionFields($class);
        $referenceFields = $this->getReferenceFields($class);
        $options[ObjectNormalizer::IGNORED_ATTRIBUTES] = array_merge(array_keys($collectionFields), array_keys($referenceFields));
        $obj = $this->serializer->denormalize($result, $class, self::REST_FORMAT, $options);

        foreach ($referenceFields as $field => $ano) {
            throw new NotImplementedException("@Reference annotation is not implemented in IdmManager yet");
        }

        foreach ($collectionFields as $field => $ano) {
            $array = $result[$field];
            if (!is_array($array)) {
                throw new InvalidArgumentException();
            }
            if($tmp = self::toUuidObjectArray($array, true)) {
                $new = LazyLoaderCollection::fromUuidList($this, $ano->getClass(), $tmp);
            } else {
                $tmp = array_map(function ($a) use ($ano) {
                    $class = $ano->getClass();
                    return $this->hydrateObject($a, $class);
                }, $array);
                $new = LazyLoaderCollection::fromObjectList($this, $ano->getClass(), $tmp);
            }
            self::setPrivateField($obj, $field, $new);
        }

        if ($tmp = $this->unitOfWork->get($class, $this->object2Id($obj))) {
            $obj = $tmp;
        } else {
            $this->unitOfWork->register($obj, true);
        }
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

        return $this->hydrateObject($result, $class);
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

    public function find(string $class, $filter = [], array $sort = [], ?int $page = 0, ?int $limit = null)
    {
        $this->throwOnNotManaged($class);

        $query = [];
        if (!empty($filter)) {
            $query['filter'] = $filter;
            $query['exact'] = is_array($filter) ? 'true' : 'false';
        }
        if (!empty($sort)) {
            $query['sort'] = $sort;
        }
        if (!empty($limit)) {
            $query['limit'] = $limit;
        }
        if (!empty($page)) {
            $query['page'] = $page;
        }

        $result = $this->get($this->createUrl($class), $query);

        $collection = $this->serializer->denormalize($result, PaginationCollection::class, self::REST_FORMAT);

        if (empty($collection))
            throw new UnsupportedClassException('Invalid PaginationCollection returned');

        foreach ($collection->items as &$item) {
            $item = $this->hydrateObject($item, $class);
        }
        return $collection;
    }

    /**
     * Removes loaded lazyLoaderObjects and checks if all other collections are actually arrays
     * @param object $object
     */
    private function checkCollections($object)
    {
        $this->throwOnNotManaged($object);

        $this->mapAnnotation($object,
            function ($class, $obj){
                throw new NotImplementedException("@Reference annotation is not implemented in IdmManager yet");
            },
            function ($class, $list) {
                if ($list instanceof LazyLoaderCollection) {
                    if ($list->isLoaded()) {
                        $list = $list->toArray();
                        foreach ($list as $item) {
                            $this->checkCollections($item);
                        }
                    }
                    return $list;
                } else {
                    $list = is_array($list) ? $list : [];
                    foreach ($list as $item) {
                        $this->checkCollections($item);
                    }
                    return $list;
                }
            }
        );
    }

    public function persist(object &$object)
    {
        $this->throwOnNotManaged($object);

        $this->checkCollections($object);

        $this->unitOfWork->persist($object);
    }

    public function remove(object $object)
    {
        $this->throwOnNotManaged($object);
        $this->unitOfWork->delete($object);
    }

    private function applyCollectionModification(object $object, array $modification)
    {
        $base_url = $this->createUrl($object, $this->object2Id($object));
        foreach ($modification as $name => $mod) {
            $url = $base_url . '/' . $name;
            foreach ($mod[0] as $added) {
                $this->post($url, new UuidObject($added));
            }
            foreach ($mod[1] as $removed) {
                $this->delete($url . '/' . $removed->toString());
            }
        }
    }

    private function areCollectionModified(array $modification): bool
    {
        $changes = false;
        foreach ($modification as $name => $mod) {
            $changes |= empty($mod[0]);
            $changes |= empty($mod[1]);
        }
        return $changes;
    }

    public function flush()
    {
        foreach ($this->unitOfWork->getObjects() as $object) {
            switch ($this->unitOfWork->getObjectState($object)) {
                case UnitOfWork::STATE_DETACHED:
                case UnitOfWork::STATE_MANAGED:
                default:
                    // nothing to do
                    continue 2;

                case UnitOfWork::STATE_CREATED:
                    if ($this->areCollectionModified($this->unitOfWork->getCollectionDiff($object))) {
                        // This would require a dependency graph if both objects are not created yet
                        throw new NotImplementedException("Creating entities with non empty Collections is not supported yet");
                    }
                    $this->hydrateObject($this->post($this->createUrl($object), $object), $object);
                    break;

                case UnitOfWork::STATE_MODIFIED:
                    $this->applyCollectionModification($object, $this->unitOfWork->getCollectionDiff($object));
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