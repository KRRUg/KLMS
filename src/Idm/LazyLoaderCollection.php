<?php

namespace App\Idm;

use App\Idm\Transfer\UuidObject;
use ArrayAccess;
use Iterator;
use Countable;
use InvalidArgumentException;
use Ramsey\Uuid\Uuid;

class LazyLoaderCollection implements ArrayAccess, Iterator, Countable
{
    private IdmManager $manager;
    private string $class;

    private array $items;

    /**
     * LazyLoaderCollection constructor.
     * @param IdmManager $manager
     * @param string $class
     * @param UuidObject[] $uuids
     */
    public function __construct(IdmManager $manager, string $class, array $uuids = [])
    {
        $this->manager = $manager;
        $this->class = $class;

        $this->items = [];
        foreach ($uuids as $uuid)
            if ($uuid instanceof UuidObject)
                $this->items[] = $uuid;
    }

    public function get($offset, bool $load = true)
    {
        if (!isset($this->items[$offset]))
            return null;
        $item =& $this->items[$offset];
        if ($load && $item instanceof UuidObject) {
            $item = $this->manager->request($this->class, $item->getUuid());
        }
        return $item;
    }

    public function uuidSet(): array
    {
        $result = [];
        foreach ($this->items as $item) {
            $result[] = $item->getUuid();
        }
        return $result;
    }

    public function offsetExists($offset): bool
    {
        return isset($this->items[$offset]);
    }

    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    public function returnLoadedObjects()
    {
        return array_filter($this->items, function ($e) { return $e instanceof $this->class; });
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

    public static function compare(LazyLoaderCollection $a, LazyLoaderCollection $b): bool
    {
        if ($a->count() != $b->count())
            return false;
        foreach ($a->items as $key => $value) {
            $uuid_a = $value->getUuid();
            $uuid_b = $b->items[$key]->getUuid();
            if ($uuid_a != $uuid_b)
                return false;
        }
        return true;
    }

    /**
     * Performs a set minus $a - $b
     * @return array The elements that are in $a but not $b
     */
    public static function minus(LazyLoaderCollection $a, LazyLoaderCollection $b): array
    {
        $result = [];
        foreach ($a->items as $v_a) {
            $found = false;
            foreach ($b->items as $v_b) {
                if($v_a->getUuid() == $v_b->getUuid())
                    $found = true;
            }
            if (!$found) {
                $result[] = $v_a;
            }
        }
        return $result;
    }
}