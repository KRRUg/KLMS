<?php


namespace App\Idm;

use ArrayAccess;
use Iterator;
use InvalidArgumentException;

class LazyLoaderCollection implements ArrayAccess, Iterator
{
    private IdmManager $manager;
    private string $class;

    private array $items;
    private int $_position = 0;

    public function __construct(IdmManager $manager, string $class, array $items = [])
    {
        $this->manager = $manager;
        $this->class = $class;
        $this->items = $items;
    }

    private function request($item)
    {
        return $this->manager->request($this->class, $item['uuid']);
    }

    public function offsetExists($offset): bool
    {
        return isset($this->items[$offset]);
    }

    public function offsetGet($offset)
    {
        if (!isset($this->items[$offset]))
            return null;

        $item = $this->items[$offset];
        if (is_a($item, $this->class))
            return $item;

        $item = $this->request($item);
        $this->items[$offset] = $item;
        return $item;
    }

    public function offsetSet($offset, $value)
    {
        if (!is_a($value, $this->class)) {
            throw new InvalidArgumentException("Incorrect type");
        }
        if (is_null($offset)) {
            $this->items[] = $value;
        } else {
            $this->items[$offset] = $value;
        }
    }

    public function offsetUnset($offset)
    {
        unset($this->items[$offset]);
    }

    public function current()
    {
        return $this[$this->_position];
    }

    public function next()
    {
        $this->_position++;
    }

    public function key()
    {
        return $this->_position;
    }

    public function valid()
    {
        return null !== $this[$this->_position];
    }

    public function rewind()
    {
        $this->_position = 0;
    }
}