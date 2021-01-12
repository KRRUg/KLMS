<?php


namespace App\Idm;

use ArrayAccess;
use Exception;
use Iterator;
use InvalidArgumentException;
use IteratorAggregate;
use Traversable;

class LazyLoaderCollection implements ArrayAccess, Iterator
{
    private IdmManager $manager;
    private string $class;

    // array <id, item>
    private array $items;
    private array $ids;

    public function __construct(IdmManager $manager, string $class, array $uuids = [])
    {
        $this->manager = $manager;
        $this->class = $class;
        $this->ids = $uuids;
        $this->items = [];
    }

    private function get($offset)
    {
        if (!isset($this->ids[$offset]))
            return null;
        $id = $this->ids[$offset];
        if (isset($this->items[$id]))
            return $this->items[$id];
        return $this->items[$id] = $this->manager->request($this->class, $id);
    }

    public function isLoaded($offset)
    {
        return isset($this->ids[$offset]) && isset($this->items[$this->ids[$offset]]);
    }

    public function offsetExists($offset): bool
    {
        return isset($this->ids[$offset]);
    }

    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    public function offsetSet($offset, $value)
    {
        if (!is_a($value, $this->class)) {
            throw new InvalidArgumentException("Incorrect type");
        }
        if ($this->isLoaded($offset))
            unset($this->items[$this->ids[$offset]]);
        $id = $value->getUuid();
        $this->ids[] = $id;
        $this->items[$id] = $value;
    }

    public function offsetUnset($offset)
    {
        if ($this->isLoaded($offset))
            unset($this->items[$this->ids[$offset]]);
        unset($this->ids[$offset]);
    }

    public function current()
    {
        return $this->get(key($this->ids));
    }

    public function next()
    {
        next($this->ids);
    }

    public function key()
    {
        return key($this->ids);
    }

    public function valid(): bool
    {
        return null !== key($this->ids);
    }

    public function rewind()
    {
        reset($this->ids);
    }
}