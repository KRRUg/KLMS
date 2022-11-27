<?php

namespace App\Twig;

use DateTime;
use DateTimeInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Description of AgoFilterExtension.
 */
class AgeFilterExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('age', $this->filterAge(...)),
        ];
    }

    public function filterAge($date): int
    {
        $from = $date instanceof DateTimeInterface ? $date : new DateTime((string) $date);
        $to = new DateTime('today');

        return $from->diff($to)->y;
    }
}
