<?php

namespace App\Idm;

use App\Idm\Transfer\UuidObject;
use InvalidArgumentException;

// TODO maybe consider removing Collection from here, as IdmManager cannot handled this when used as object property
class LazyLoaderCollection implements Collection
{
    private readonly IdmManager $manager;
    private readonly string $class;

    private array $items;
    private bool $loaded;

    public function __construct(IdmManager $manager, string $class)
    {
        $this->manager = $manager;
        $this->class = $class;
        $this->loaded = false;
        $this->items = [];
    }

    public static function fromUuidList(IdmManager $manager, string $class, array $uuids): Collection
    {
        $result = new self($manager, $class);
        $result->loaded = false;

        foreach ($uuids as $uuid) {
            if ($uuid instanceof UuidObject) {
                $result->items[] = $uuid;
            }
        }

        return $result;
    }

    public static function fromObjectList(IdmManager $manager, string $class, array $objects): Collection
    {
        $result = new self($manager, $class);
        $result->loaded = true;

        foreach ($objects as $object) {
            if ($object::class === $class) {
                $result->items[] = $object;
            }
        }

        return $result;
    }

    public function __sleep(): array
    {
        return ['class', 'items', 'loaded'];
    }

    private function load(): void
    {
        $this->items = array_map(fn (UuidObject $object) => $this->manager->request($this->class, $object->getUuid()), $this->items);
        $this->loaded = true;
    }

    public function get($offset): mixed
    {
        if (!isset($this->items[$offset])) {
            return null;
        }
        if (!$this->loaded) {
            $this->load();
        }

        return $this->items[$offset];
    }

    public function isEmpty(): bool
    {
        return empty($this->items);
    }

    public function toArray(bool $load = true): array
    {
        if (!$this->loaded && $load) {
            $this->load();
        }

        return $this->items;
    }

    public function getUuid($offset): ?UuidObject
    {
        if (!isset($this->items[$offset])) {
            return null;
        }
        $item = $this->items[$offset];

        return $this->loaded ? UuidObject::fromObject($item) : $item;
    }

    public function toUuidArray(): array
    {
        return array_map(fn ($item) => $this->loaded ? UuidObject::fromObject($item) : $item, $this->items);
    }

    public function offsetExists($offset): bool
    {
        return isset($this->items[$offset]);
    }

    public function offsetGet($offset): mixed
    {
        return $this->get($offset);
    }

    public function isLoaded(): bool
    {
        return $this->loaded;
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

    public function current(): mixed
    {
        return $this->get(key($this->items));
    }

    public function next(): void
    {
        next($this->items);
    }

    public function key(): mixed
    {
        return key($this->items);
    }

    public function valid(): bool
    {
        return null !== key($this->items);
    }

    public function rewind(): void
    {
        reset($this->items);
    }

    public function count(): int
    {
        return count($this->items);
    }
}
