<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\Markup;
use Twig\TwigFilter;

/**
 * Renders a badge with the passed text if the value is empty.
 */
class DefaultBadgeFilterExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('defaultBadge', $this->filterDefaultBadge(...)),
        ];
    }

    public function filterDefaultBadge($value, $defaultText, $badgeStyle = 'light'): Markup
    {
        if (!empty($value)) {
            return $value;
        }

        $badge = "<span class=\"badge badge-$badgeStyle \">$defaultText</span>";

        return new Markup($badge, 'UTF-8');
    }
}
