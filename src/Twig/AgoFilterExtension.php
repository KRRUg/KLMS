<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Description of AgoFilterExtension
 *
 * @author m8sch
 */
class AgoFilterExtension extends AbstractExtension {

    public function getFilters() {
        return [
            new TwigFilter('ago', [$this, 'filterAgo']),
        ];
    }

    public function filterAgo($date) {
        $periods = array("Sek.", "Min.", "Std.", "Tag(e)", "Woche(n)", "Monat(e)", "Jahr(e)");
        $lengths = array("60", "60", "24", "7", "4.33", "12");

        $now = time();
        $difference = $now - $date->getTimestamp();

        for($j = 0; $difference >= $lengths[$j] && $j < count($lengths)-1; $j++) {
            $difference /= $lengths[$j];
        }
        
        $difference = round($difference);
        
        return "$difference $periods[$j]";
    }

}
