<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Renders a badge with the passed text if the value is empty.
 *
 * @author Coffeemaker
 */
class DefaultBadgeFilterExtension extends AbstractExtension
{
    public function getFilters()
    {
        return [
            new TwigFilter('defaultBadge', $this->filterDefaultBadge(...)),
        ];
    }

    public function filterDefaultBadge($value, $defaultText, $badgeStyle = 'light')
    {
        if (!empty($value)) {
            return $value;
        }

        $badge = "<span class=\"badge badge-$badgeStyle \">$defaultText</span>";

        return new \Twig\Markup($badge, 'UTF-8');
    }
}
