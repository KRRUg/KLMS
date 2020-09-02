<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Description of AgoFilterExtension
 *
 * @author m8sch
 */
class AgoFilterExtension extends AbstractExtension
{
    public function getFilters()
    {
        return [
            new TwigFilter('ago', [$this, 'filterAgo']),
        ];
    }

    public function filterAgo($date)
    {
        $periods_s = array("Sek", "Min", "Std", "Tag", "Woche", "Monat", "Jahr");
        $periods_p = array("Sek", "Min", "Std", "Tage", "Wochen", "Monate", "Jahre");
        $lengths = array("60", "60", "24", "7", "4.33", "12");

        $now = time();
        $difference = $now - $date->getTimestamp();

        if ($difference < 90)
            return "jetzt";

        for($j = 0; $difference >= $lengths[$j] && $j < count($lengths)-1; $j++) {
            $difference /= $lengths[$j];
        }
        
        $difference = round($difference);
        return "vor " . $difference . " " . ($difference == 1 ? $periods_s[$j] : $periods_p[$j]);
    }
}
