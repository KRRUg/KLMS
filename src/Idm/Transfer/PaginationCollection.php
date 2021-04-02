<?php


namespace App\Idm\Transfer;

final class PaginationCollection
{
    public int $total;

    public int $count;

    public array $items;

    public function __construct(array $items = [], $total = 0)
    {
        $this->items = $items;
        $this->total = $total;
        $this->count = count($items);
    }
}