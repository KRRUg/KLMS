<?php

namespace App\Idm;

use App\Idm\Annotation\Collection;
use App\Idm\Annotation\Reference;
use App\Idm\Exception\NotImplementedException;
use Closure;
use ReflectionClass;

class UnitOfWork
{
    /**
     * @var array spl_object_id => object
     */
    private array $objects;

    /**
     * @var array Original values of the objects
     */
    private array $orig;

    /**
     * @var array object ids marked for deletion
     */
    private array $delete_ids;

    /**
     * @var array uuid => spl_object_id for existing objects
     */
    private array $id_ref;

    /**
     * UnitOfWork constructor.
     */
    public function __construct()
    {
        $this->objects = [];
        $this->id_ref = [];
        $this->delete_ids = [];
        $this->orig = [];
    }

    /**
     * Registers a new object to the UoW.
     * Note: If there is already another object with the same id, the newer Object will be returned on get.
     *
     * @param bool $existing If the object represents an existing object from the IDM (i.e. has the id set)
     */
    private function register(object $obj, bool $existing): void
    {
        $class = $obj::class;
        $id = spl_object_id($obj);

        if ($existing) {
            $this->id_ref[$class.ObjectInspector::object2Id($obj)] = $id;
        }
        if (array_key_exists($id, $this->objects)) {
            return;
        }

        $this->objects[$id] = $obj;

        if ($existing) {
            $this->backUp($obj);
        }
    }

    public function delete(object $obj): void
    {
        if (!$this->isAttached($obj)) {
            return;
        }
        if ($this->isNew($obj)) {
            unset($this->objects[spl_object_id($obj)]);
        }
        $id = spl_object_id($obj);
        $this->delete_ids[$id] = $id;
    }

    public function get(string $class, string $id)
    {
        $key = $class.$id;
        if (isset($this->id_ref[$key])) {
            return $this->objects[$this->id_ref[$key]];
        }

        return null;
    }

    public function isDirty($object): bool
    {
        if ($this->isNew($object)) {
            return false;
        }

        $id = spl_object_id($object);

        return !ObjectInspector::compareObjects($this->objects[$id], $this->orig[$id]);
    }

    public function isNew($object): bool
    {
        return $this->isAttached($object) && !array_key_exists(spl_object_id($object), $this->orig);
    }

    public function isAttached($object): bool
    {
        return array_key_exists(spl_object_id($object), $this->objects);
    }

    /**
     * Attach an existing object (hydrated from the IDM)
     */
    public function attach(object $object): void
    {
        if (empty(ObjectInspector::object2Id($object))) {
            return;
        }
        $this->register($object, true);
    }

    public function persist(object $object, array &$already_done = []): void
    {
        $id = spl_object_id($object);
        if (isset($already_done[$id])) {
            return;
        } else {
            $already_done[$id] = true;
        }

        // make sure new entities are persisted
        $this->register($object, false);
        // finally, check for annotated properties
        $this->foreachAnnotation($object,
            function ($class, $obj) use (&$already_done) {
                $this->persist($obj, $already_done);
            },
            function ($class, $list) use ($already_done) {
                if (is_null($list)) {
                    return;
                }
                if ($list instanceof LazyLoaderCollection && !$list->isLoaded()) {
                    return;
                }
                foreach ($list as $loadedObj) {
                    $this->persist($loadedObj, $already_done);
                }
            }
        );
    }

    final public const STATE_DETACHED = 0;
    final public const STATE_MANAGED = 1;
    final public const STATE_CREATED = 2;
    final public const STATE_MODIFIED = 3;
    final public const STATE_DELETE = 4;

    public function getObjectState(object $object): int
    {
        $id = spl_object_id($object);
        if (!array_key_exists($id, $this->objects)) {
            return self::STATE_DETACHED;
        }

        if (array_key_exists($id, $this->delete_ids)) {
            return self::STATE_DELETE;
        }

        if (!array_key_exists($id, $this->orig)) {
            return self::STATE_CREATED;
        }

        if (!ObjectInspector::compareObjects($this->objects[$id], $this->orig[$id])) {
            return self::STATE_MODIFIED;
        }

        return self::STATE_MANAGED;
    }

    public function flush(object $object = null): void
    {
        if ($object === null) {
            foreach ($this->objects as $o) {
                $this->flush($o);
            }
        } else {
            $id = spl_object_id($object);
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

    private function backUp(object $obj): void
    {
        $clone = clone $obj;
        $this->mapAnnotation($clone,
            function ($class, $obj): never {
                throw new NotImplementedException('@Reference annotation is not implemented in IdmManager yet');
            },
            function ($class, $list) {
                if ($list instanceof LazyLoaderCollection) {
                    return $list->toArray(false);
                }

                return $list;
            }
        );
        $this->orig[spl_object_id($obj)] = $clone;
    }

    /**
     * @param object $object Object to check for collection modifications
     *
     * @return array prop_name => [[add], [remove]] where add and remove are sets of ids of the corresponding objects
     */
    public function getCollectionDiff(object $object): array
    {
        $id = spl_object_id($object);

        if (!isset($this->objects[$id])) {
            return [];
        }

        return $this->compareCollections($this->objects[$id], $this->orig[$id] ?? null);
    }

    /**
     * @param object      $object    Object to compare
     * @param object|null $reference Object to compare to
     *
     * @return array prop_name => [[add], [remove]] where add and remove are sets of ids of the corresponding objects
     */
    private function compareCollections(object $object, ?object $reference): array
    {
        $result = [];
        $ref = new ReflectionClass($object);

        foreach ($ref->getProperties() as $property) {
            if ($property->getAttributes(Collection::class)) {
                $v_a = $property->getValue($object);
                if (is_null($v_a)) {
                    $result[$property->getName()] = [[], []];
                    continue;
                }
                $v_b = is_null($reference) ? [] : $property->getValue($reference);
                $v_a = ($v_a instanceof LazyLoaderCollection) ? $v_a->toArray(false) : $v_a;
                $v_b = ($v_b instanceof LazyLoaderCollection) ? $v_b->toArray(false) : $v_b;
                $v_a = array_map(fn ($i_a) => ObjectInspector::object2Id($i_a), $v_a);
                $v_b = array_map(fn ($i_b) => ObjectInspector::object2Id($i_b), $v_b);
                $result[$property->getName()] = [array_diff($v_a, $v_b), array_diff($v_b, $v_a)];
            }
        }

        return $result;
    }

    private function foreachAnnotation(object $object, Closure $closureReference, Closure $closureCollection): void
    {
        $reflection = new ReflectionClass($object);

        foreach ($reflection->getProperties() as $property) {
            if ($attributes = $property->getAttributes(Collection::class)) {
                $closureCollection($attributes[0]->newInstance()->getClass(), $property->getValue($object));
            } elseif ($attributes = $property->getAttributes(Reference::class)) {
                $closureReference($attributes[0]->newInstance()->getClass(), $property->getValue($object));
            }
        }
    }

    private function mapAnnotation(object $object, Closure $closureReference, Closure $closureCollection): void
    {
        $reflection = new ReflectionClass($object);

        foreach ($reflection->getProperties() as $property) {
            if ($attributes = $property->getAttributes(Collection::class)) {
                $property->setValue($object, $closureCollection($attributes[0]->newInstance()->getClass(), $property->getValue($object)));
            } elseif ($attributes = $property->getAttributes(Reference::class)) {
                $property->setValue($object, $closureReference($attributes[0]->newInstance()->getClass(), $property->getValue($object)));
            }
        }
    }

    public function getObjects(): array
    {
        return array_values($this->objects);
    }

    public function getDirtyProperties(object $object): ?array
    {
        $id = spl_object_id($object);
        if (array_key_exists($id, $this->orig)) {
            return ObjectInspector::diffObjects($object, $this->orig[$id]);
        }
        return null;
    }
}
