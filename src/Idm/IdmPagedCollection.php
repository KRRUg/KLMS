<?php


namespace App\Idm;


use ArrayAccess;
use Countable;
use InvalidArgumentException;
use Iterator;

class IdmPagedCollection implements ArrayAccess, Iterator, Countable
{
    private IdmManager $manager;

    private string $class;
    private array $filter;
    private array $sort;

    private int $total;
    private int $page_size;

    private array $items;

    private function __construct(IdmManager $manager, string $class, array $filter, array $sort, int $page_size)
    {
        $this->manager = $manager;
        $this->class = $class;
        $this->filter = $filter;
        $this->sort = $sort;
        $this->page_size = $page_size;
        $this->items = [];
    }

    public static function create(IdmManager $manager, string $class, array $filter = [], array $sort = [], int $page_size = 10): self
    {
        $result = new self($manager, $class, $filter, $sort, $page_size);
        $result->request(0);
        return $result;
    }

    private function request(int $offset): void
    {
        $page = intdiv($offset, $this->page_size);
        $result = $this->manager->find($this->class, $this->filter, $this->sort, $page, $this->page_size);
        $this->total = $result->total;
        for ($i = 0; $i < $result->count; $i++) {
            $this->items[$page * $this->page_size + $i] = $result->items[$i];
        }
    }

    public function offsetGet($offset): ?object
    {
        if (!$this->offsetExists($offset))
            return null;

        if (!isset($this->items[$offset]))
            $this->request($offset);

        return isset($this->items[$offset]) ? $this->items[$offset] : null;
    }

    public function offsetExists($offset): bool
    {
        return $offset >= 0 && $offset < $this->total;
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

    public function count(): int
    {
        return $this->total;
    }

    public function current()
    {
        // TODO: Implement current() method.
    }

    public function next()
    {
        // TODO: Implement next() method.
    }

    public function key()
    {
        // TODO: Implement key() method.
    }

    public function valid()
    {
        // TODO: Implement valid() method.
    }

    public function rewind()
    {
        // TODO: Implement rewind() method.
    }
}