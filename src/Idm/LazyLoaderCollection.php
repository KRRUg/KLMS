<?php

namespace App\Idm;

use App\Idm\Transfer\UuidObject;
use ArrayAccess;
use Iterator;
use Countable;
use InvalidArgumentException;

class LazyLoaderCollection implements ArrayAccess, Iterator, Countable
{
    private IdmManager $manager;
    private string $class;

    private array $items;
    private bool $loaded;

    /**
     * LazyLoaderCollection constructor.
     * @param IdmManager $manager
     * @param string $class
     */
    public function __construct(IdmManager $manager, string $class)
    {
        $this->manager = $manager;
        $this->class = $class;
        $this->loaded = false;
        $this->items = [];
    }

    public static function fromUuidList(IdmManager $manager, string $class, array $uuids)
    {
        $result = new self($manager, $class);
        $result->loaded = false;

        foreach ($uuids as $uuid)
            if ($uuid instanceof UuidObject)
                $result->items[] = $uuid;

        return $result;
    }

    public static function fromObjectList(IdmManager $manager, string $class, array $objects)
    {
        $result = new self($manager, $class);
        $result->loaded = true;

        foreach ($objects as $object)
            if (get_class($object) === $class)
                $result->items[] = $object;

        return $result;
    }

    public function __sleep(): array
    {
        return ['class', 'items', 'loaded'];
    }

    private function load()
    {
        $this->items = array_map(function (UuidObject $object) {
            return $this->manager->request($this->class, $object->getUuid());
        }, $this->items);
        $this->loaded = true;
    }

    public function get($offset)
    {
        if (!isset($this->items[$offset]))
            return null;
        if (!$this->loaded)
            $this->load();
        return $this->items[$offset];
    }

    public function toArray(bool $load = true): array
    {
        if (!$this->loaded && $load)
            $this->load();
        return $this->items;
    }

    public function getUuid($offset): ?UuidObject
    {
        if (!isset($this->items[$offset]))
            return null;
        $item = $this->items[$offset];
        if (!$this->loaded)
            return $item;
        return UuidObject::fromObject($item);
    }

    public function offsetExists($offset): bool
    {
        return isset($this->items[$offset]);
    }

    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    public function isLoaded(): bool
    {
        return $this->loaded;
    }

    public function offsetSet($offset, $value)
    {
        if (!is_a($value, $this->class)) {
            throw new InvalidArgumentException("Incorrect type");
        }
        $this->items[$offset] = $value;
    }

    public function offsetUnset($offset)
    {
        unset($this->items[$offset]);
    }

    public function current()
    {
        return $this->get(key($this->items));
    }

    public function next()
    {
        next($this->items);
    }

    public function key()
    {
        return key($this->items);
    }

    public function valid(): bool
    {
        return null !== key($this->items);
    }

    public function rewind()
    {
        reset($this->items);
    }

    public function count(): int
    {
        return count($this->items);
    }
}