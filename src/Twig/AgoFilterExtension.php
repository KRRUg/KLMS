<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Description of AgoFilterExtension.
 */
class AgoFilterExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('ago', $this->filterAgo(...)),
        ];
    }

    public function filterAgo($date): string
    {
        $periods_s = ['Sek', 'Min', 'Std', 'Tag', 'Woche', 'Monat', 'Jahr'];
        $periods_p = ['Sek', 'Min', 'Std', 'Tagen', 'Wochen', 'Monaten', 'Jahren'];
        $lengths = ['60', '60', '24', '7', '4.33', '12'];

        $now = time();
        $difference = $now - $date->getTimestamp();

        if ($difference < 90) {
            return 'jetzt';
        }

        for ($j = 0; $difference >= $lengths[$j] && $j < count($lengths) - 1; ++$j) {
            $difference /= $lengths[$j];
        }

        $difference = round($difference);

        return 'vor '.$difference.' '.($difference == 1 ? $periods_s[$j] : $periods_p[$j]);
    }
}
