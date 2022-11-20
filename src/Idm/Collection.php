<?php

namespace App\Idm;

use ArrayAccess;
use Countable;
use Iterator;

// TODO change to IteratorAggregate
interface Collection extends ArrayAccess, Iterator, Countable
{
    public function get($offset): mixed;

    public function isEmpty(): bool;
}
