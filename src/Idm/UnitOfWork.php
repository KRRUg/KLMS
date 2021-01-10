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
     * @var array set of uuids that are dirty
     */
    public array $dirty;

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
    }

    public function get($id)
    {
        if (array_key_exists($id, $this->objects)) {
            return $this->objects[$id];
        }
        return null;
    }
}