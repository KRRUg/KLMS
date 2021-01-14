<?php


namespace App\Idm;

use App\Idm\Annotation\Collection;
use App\Idm\Annotation\Reference;
use App\Idm\Exception\UnsupportedClassException;
use Closure;
use Doctrine\Common\Annotations\Reader;
use ReflectionClass;
use Symfony\Component\Intl\Exception\NotImplementedException;

class UnitOfWork
{
    private IdmManager $manager;
    private Reader $annotationReader;

    /**
     * @var array spl_object_id => object
     */
    private array $objects;

    /**
     * @var array Original values of the objects
     */
    private array $orig;

    /**
     * @var array Object ids marked for deletion.
     */
    private array $delete_ids;

    /**
     * @var array Object ids marked as persist.
     */
    private array $persist_ids;

    /**
     * @var array uuid => spl_object_id for existing objects
     */
    private array $id_ref;

    /**
     * UnitOfWork constructor.
     */
    public function __construct(IdmManager $manager, Reader $annotationReader)
    {
        $this->manager = $manager;
        $this->annotationReader = $annotationReader;

        $this->objects = [];
        $this->id_ref = [];
        $this->delete_ids = [];
        $this->persist_ids = [];
    }

    public function register(object &$obj, bool $existing = false)
    {
        if (!$this->manager->isManaged($obj)) {
            throw new UnsupportedClassException();
        }

        $id = spl_object_id($obj);
        if (array_key_exists($id, $this->objects)) {
            return;
        }

        $this->objects[$id] = $obj;
        $this->id_ref[get_class($obj) . $this->manager->object2Id($obj)] = $obj;

        if ($existing) {
            $this->backUp($obj);
        }
    }

    public function delete(object $obj)
    {
        if (!$this->isAttached($obj)) {
            return;
        }
        $id = spl_object_id($obj);
        $this->delete_ids[$id] = $id;
    }

    public function get(string $class, string $id)
    {
        $key = $class . $id;
        if (isset($this->id_ref[$key])) {
            return $this->objects[$this->id_ref[$key]];
        }
        return null;
    }

    public function isDirty($object): bool
    {
        if ($this->isNew($object))
            return false;

        $id = spl_object_id($object);
        return !$this->manager->compareObjects($this->objects[$id], $this->orig[$id]);
    }

    public function isNew($object): bool
    {
        return $this->isAttached($object) && !array_key_exists(spl_object_id($object), $this->orig);
    }

    public function isAttached($object): bool
    {
        return array_key_exists(spl_object_id($object), $this->objects);
    }

    public function persist(object &$object)
    {
        $id = spl_object_id($object);

        // check if already marked for persist
        if (array_key_exists($id, $this->persist_ids))
            return;
        // make sure new entities are persisted
        $this->register($object, false);
        // and register object to be persisted
        $this->persist_ids[$id] = $id;
        // finally, check for annotated properties
        $this->foreachAnnotation($object,
            function ($class, $obj) {
                $this->persist($obj);
            },
            function ($class, $list) {
                foreach ($list->getLoadedItems() as &$loadedObj) {
                    $this->persist($loadedObj);
                }
            }
        );
    }

    public function getModifiedObjects()
    {
        return array_map(function ($id) { return $this->objects[$id]; }, array_merge(array_keys($this->persist_ids), array_keys($this->delete_ids)));
    }

    public const STATE_DETACHED = 0;
    public const STATE_MANAGED = 1;
    public const STATE_CREATED = 2;
    public const STATE_MODIFIED = 3;
    public const STATE_DELETE = 4;

    public function getObjectState(object $object)
    {
        $id = spl_object_id($object);
        if (!array_key_exists($id, $this->objects))
            return self::STATE_DETACHED;

        if (array_key_exists($id, $this->delete_ids))
            return self::STATE_DELETE;

        if (!array_key_exists($id, $this->orig))
            return self::STATE_CREATED;

        if (!$this->manager->compareObjects($this->objects[$id], $this->orig[$id]))
            return self::STATE_MODIFIED;

        return self::STATE_MANAGED;
    }

    public function flush(object $object = null)
    {
        if ($object === null) {
            foreach ($this->objects as $o) {
                $this->flush($o);
            }
        } else {
            $id = spl_object_id($object);
            unset($this->persist_ids[$id]);
            if (!array_key_exists($id, $this->objects)) {
                return;
            }
            if (array_key_exists($id, $this->delete_ids)) {
                unset($this->delete_ids[$id]);
                unset($this->objects[$id]);
                unset($this->orig[$id]);
            } else {
                $this->backUp($object);
            }
        }
    }

    private function backUp(object $obj)
    {
        $clone = clone $obj;
        $this->mapAnnotation($clone,
            function ($class, $obj) {
                throw new NotImplementedException("@Reference annotation is not implemented in IdmManager yet");
            },
            function ($class, $list) {
                return clone $list;
            }
        );
        $this->orig[spl_object_id($obj)] = $clone;
    }

    /**
     * @param object $object Object to check for collection modifications
     * @return array prop_name => [[add], [remove]]
     */
    public function getCollectionDiff(object $object)
    {
        $id = spl_object_id($object);

        if (!isset($this->objects[$id]) || !isset($this->orig[$id]))
            return [];

        return $this->compareCollections($this->objects[$id], $this->orig[$id]);
    }

    /**
     * @param object $object Object to compare
     * @param object $reference Object to compare to
     * @return array prop_name => [[add], [remove]]
     */
    private function compareCollections(object $object, object $reference): array
    {
        if (get_class($object) != get_class($reference))
            return [];

        $result = [];
        $ref = new ReflectionClass($object);

        foreach ($ref->getProperties() as $property) {
            if($ano = $this->annotationReader->getPropertyAnnotation($property, Collection::class)) {
                $property->setAccessible(true);
                $v_a = $property->getValue($object);
                $v_b = $property->getValue($reference);
                $result[$property->getName()] = [LazyLoaderCollection::minus($v_a, $v_b), LazyLoaderCollection::minus($v_b, $v_a)];
            }
        }

        return $result;
    }

    private function foreachAnnotation(object $object, Closure $closureReference, Closure $closureCollection)
    {
        $reflection = new ReflectionClass($object);

        foreach ($reflection->getProperties() as $property) {
            if($ano = $this->annotationReader->getPropertyAnnotation($property, Collection::class)) {
                $property->setAccessible(true);
                $closureCollection($ano->getClass(), $property->getValue($object));
            } elseif ($ano = $this->annotationReader->getPropertyAnnotation($property, Reference::class)) {
                $property->setAccessible(true);
                $closureReference($ano->getClass(), $property->getValue($object));
            }
        }
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
}