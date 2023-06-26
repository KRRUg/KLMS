<?php

namespace App\Idm;

use ArrayAccess;
use Countable;
use Iterator;

// TODO change to IteratorAggregate
// TODO add ArrayCollection and remove array type from User::clans and Clan::users and Clan::admins
interface Collection extends ArrayAccess, Iterator, Countable
{
    public function get($offset): mixed;

    public function isEmpty(): bool;
}
