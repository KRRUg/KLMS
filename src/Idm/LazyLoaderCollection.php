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

    // the items array with management information
    private array $items;

    private const I_ID = 0;
    private const I_OBJ = 1;
    private const I_ADD = 2;
    private const I_REM = 3;

    public function __construct(IdmManager $manager, string $class, array $uuids = [])
    {
        $this->manager = $manager;
        $this->class = $class;

        $this->items = [];
        foreach ($uuids as $uuid) {
            $this->items[] = [self::I_ID => $uuid, self::I_OBJ => null, self::I_ADD => false, self::I_REM => false];
        }
    }

    private function get($offset)
    {
        if (!isset($this->items[$offset]))
            return null;
        $item =& $this->items[$offset];
        if ($item[self::I_REM])
            return null;
        if (empty($item[self::I_OBJ]))
            $item[self::I_OBJ] = $this->manager->request($this->class, $item[self::I_ID]);

        return $item[self::I_OBJ];
    }

    public function offsetExists($offset): bool
    {
        return isset($this->items[$offset]) && !$this->items[$offset][self::I_REM];
    }

    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    public function returnLoadedObjects()
    {
        $result = [];
        foreach ($this->items as &$value) {
            if (!$value[self::I_REM] && !empty($value[self::I_OBJ]))
                $result[] = $value[self::I_OBJ];
        }
        return $result;
    }

    public function offsetSet($offset, $value)
    {
        if (!is_a($value, $this->class)) {
            throw new InvalidArgumentException("Incorrect type");
        }
        // move element to new position
        if (isset($this->items[$offset])) {
            $item = $this->items[$offset];
            $item[self::I_REM] = true;
            if (!$item[self::I_ADD])
                $this->items[] = $item;
        }
        $this->items[$offset] = [self::I_ID => null, self::I_OBJ => $value, self::I_ADD => true, self::I_REM => false];
    }

    public function offsetUnset($offset)
    {
        if (!$this->offsetExists($offset))
            return;
        $this->items[$offset][self::I_REM] = true;
    }

    public function current()
    {
        return $this->get(key($this->items));
    }

    public function next()
    {
        while($val = next($this->items)) {
            if (!$val[self::I_REM])
                break;
        }
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
        if (!($val = reset($this->items)))
            return;
        do {
            if (!$val[self::I_REM])
                break;
        } while($val = next($this->items));
    }
}