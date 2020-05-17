<?php


namespace App\Transfer;

use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;


final class PaginationCollection
{
    /**
     * @Groups({"dto"})
     * @Assert\Type('integer')
     */
    public $total;
    /**
     * @Groups({"dto"})
     * @Assert\Type('integer')
     */
    public $count;
    /**
     * @Groups({"dto"})
     */
    public $items;
    /**
     * @Groups({"dto"})
     */
    public $_links = array();

    public function __construct(array $items, int $total)
    {
        $this->items = $items;
        $this->total = $total;
        $this->count = count($items);
    }

    public function addLink($ref, $url)
    {
        $this->_links[$ref] = $url;
    }
}