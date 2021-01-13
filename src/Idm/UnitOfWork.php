<?php


namespace App\Idm;


use App\Idm\Exception\UnsupportedClassException;

class UnitOfWork
{
    private IdmManager $manager;

    /**
     * @var array spl_object_id => object
     */
    private array $objects;

    /**
     * @var array uuid => spl_object_id for existing objects
     */
    private array $id_ref;

    /**
     * @var array Original values of the objects
     */
    private array $orig;

    /**
     * @var array Object ids marked for deletion
     */
    private array $delete;

    /**
     * UnitOfWork constructor.
     */
    public function __construct(IdmManager $manager)
    {
        $this->manager = $manager;
        $this->objects = [];
        $this->id_ref = [];
        $this->delete = [];
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
        if ($existing)
            $this->orig[$id] = clone $obj;
    }

    public function delete(object $obj)
    {
        if (!$this->isAttached($obj)) {
            return;
        }
        $this->delete[] = spl_object_id($obj);
    }

    public function get($id)
    {
        if (array_key_exists($id, $this->id_ref)) {
            return $this->objects[$this->id_ref[$id]];
        }
        return null;
    }

    public function isDirty($object): bool
    {
        $id = spl_object_id($object);
        if ($this->isNew($object))
            return true;
        return $this->objects[$id] !== $this->orig[$id];
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
        $this->register($loadedObj);
        foreach ($object as $key => $value) {
            if ($value instanceof LazyLoaderCollection) {
                foreach ($value->returnLoadedObjects() as &$loadedObj) {
                    $this->persist($loadedObj);
                }
            }
        }
    }

    public function getModifiedObjects()
    {
        $result = [];
        // TODO check if a changed lazy list is recognized as a changed object
        foreach ($this->objects as $key => &$o) {
            if (isset($this->orig[$key]) && !array_search($key, $this->delete) && $this->orig[$key] != $o) {
                $result[] = &$o;
            }
        }
        return $result;
    }

    public function getNewObjects()
    {
        $result = [];
        foreach ($this->objects as $key => &$o) {
            if (!isset($this->orig[$key])) {
                $result[] = &$o;
            }
        }
        return $result;
    }

    public function getDeletedObjects()
    {
        $result = [];
        foreach ($this->delete as $id) {
            $result[] = &$this->objects[$id];
        }
        return $result;
    }

    public function flush()
    {
        foreach ($this->delete as $id) {
            unset($this->objects[$id]);
        }
        $this->delete = [];
        $this->orig = [];
        foreach ($this->objects as $key => $o) {
            $this->orig[$key] = $o;
        }
    }
}