<?php


namespace App\Idm;


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
     * UnitOfWork constructor.
     */
    public function __construct(IdmManager $manager)
    {
        $this->manager = $manager;
        $this->objects = [];
        $this->id_ref = [];
    }

    public function register(object &$obj)
    {
        if (!$this->manager->isManaged($obj)) {
            return;
        }

        $id = spl_object_id($obj);
        if (array_key_exists($id, $this->objects)) {
            $obj = $this->objects[$id];
            return;
        }
        $this->orig[$id] = clone $obj;
        $this->objects[$id] = $obj;
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
}