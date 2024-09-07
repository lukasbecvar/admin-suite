<?php

namespace App\Twig;

use Twig\TwigFilter;
use Twig\Extension\AbstractExtension;

/**
 * Class LinkifyExtension
 *
 * LinkifyExtension is a custom Twig extension for converting links in text to clickable HTML links
 *
 * @package App\Twig
 */
class LinkifyExtension extends AbstractExtension
{
    /**
     * Get the filters provided by this extension
     *
     * @return array<TwigFilter> An array of Twig filters
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('linkify', [$this, 'linkifyText'], ['is_safe' => ['html']])
        ];
    }

    /**
     * Converts links in the given text to clickable HTML links
     *
     * @param string $text The input text
     *
     * @return string|null The text with clickable HTML links
     */
    public function linkifyText(string $text): ?string
    {
        return preg_replace(
            '/(https?:\/\/[^\s]+)/',
            '<a href="$1" class="link" target="_blank">$1</a>',
            $text
        );
    }
}
