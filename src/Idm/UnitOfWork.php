<?php


namespace App\Idm;


class UnitOfWork
{
    private IdmManager $manager;

    /**
     * @var array uuid => object
     */
    public array $objects;

    /**
     * @var array uuid => hash(object)
     */
    public array $loaded;


    /**
     * UnitOfWork constructor.
     */
    public function __construct(IdmManager $manager)
    {
        $this->manager = $manager;
        $this->objects = [];
        $this->dirty = [];
    }

    public function register(object &$obj)
    {
        if (!IdmManager::isObjectManaged($obj)) {
            return;
        }

        // TODO this could be done with an @Id Annotation
        $id = $obj->getUuid()->toString();

        if (array_key_exists($id, $this->objects)) {
            $obj = $this->objects[$id];
            return;
        }
        $this->objects[$id] = $obj;
        $this->loaded[$id] = spl_object_hash($obj);
    }

    public function get($id)
    {
        if (array_key_exists($id, $this->objects)) {
            return $this->objects[$id];
        }
        return null;
    }

    public function isDirty($id)
    {
        if (array_key_exists($id, $this->objects)) {
            return spl_object_hash($this->objects[$id]) !== $this->loaded[$id];
        }
        return false;
    }

    public function isAttached($id)
    {
        return array_key_exists($id, $this->objects);
    }
}