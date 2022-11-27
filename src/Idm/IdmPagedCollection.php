<?php

namespace App\Idm;

use InvalidArgumentException;

class IdmPagedCollection implements Collection
{
    private readonly IdmManager $manager;

    private string|array $filter;
    private readonly array $sort;
    private readonly string $class;

    private readonly bool $case;
    private readonly bool $fuzzy;

    private int $total;
    private readonly int $page_size;
    private int $position;

    private array $items;

    private function __construct(IdmManager $manager, string $class, $filter, bool $fuzzy, bool $case, array $sort, int $page_size)
    {
        $this->manager = $manager;
        $this->class = $class;
        $this->filter = $filter;
        $this->sort = $sort;
        $this->page_size = $page_size;
        $this->items = [];
        $this->position = 0;
        $this->case = $case;
        $this->fuzzy = $fuzzy;
    }

    public static function create(IdmManager $manager, string $class, $filter = [], bool $fuzzy = false, bool $case = false, array $sort = [], int $page_size = 10): self
    {
        $result = new self($manager, $class, $filter, $fuzzy, $case, $sort, $page_size);
        $result->request(0);

        return $result;
    }

    private function request(int $offset): void
    {
        $page = intdiv($offset, $this->page_size);
        $result = $this->manager->find($this->class, $this->filter, $this->fuzzy, $this->case, $this->sort, $page + 1, $this->page_size);
        $this->total = $result->total;
        for ($i = 0; $i < $result->count; ++$i) {
            $this->items[$page * $this->page_size + $i] = $result->items[$i];
        }
    }

    public function getPage(int $page, int $limit): array
    {
        $result = [];
        for ($i = 0; $i < $limit; ++$i) {
            $val = $this[($page - 1) * $limit + $i];
            if (!empty($val)) {
                $result[$i] = $val;
            }
        }

        return $result;
    }

    public function get(mixed $offset): mixed
    {
        if (!$this->offsetExists($offset)) {
            return null;
        }

        if (!isset($this->items[$offset])) {
            $this->request($offset);
        }

        return $this->items[$offset] ?? null;
    }

    public function offsetGet($offset): mixed
    {
        return $this->get($offset);
    }

    public function offsetExists($offset): bool
    {
        return is_int($offset) && $offset >= 0 && $offset < $this->total;
    }

    public function offsetSet($offset, $value): void
    {
        if (!is_a($value, $this->class)) {
            throw new InvalidArgumentException('Incorrect type');
        }
        $this->items[$offset] = $value;
    }

    public function offsetUnset($offset): void
    {
        unset($this->items[$offset]);
    }

    public function count(): int
    {
        return $this->total;
    }

    public function current(): mixed
    {
        return $this->offsetGet($this->position);
    }

    public function next(): void
    {
        ++$this->position;
    }

    public function key(): int
    {
        return $this->position;
    }

    public function valid(): bool
    {
        return $this->offsetExists($this->position);
    }

    public function rewind(): void
    {
        $this->position = 0;
    }

    public function isEmpty(): bool
    {
        return $this->total == 0;
    }
}
