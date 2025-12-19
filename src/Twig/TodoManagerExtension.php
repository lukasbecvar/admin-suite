<?php

namespace App\Twig;

use Twig\TwigFilter;
use Twig\TwigFunction;
use App\Manager\TodoManager;
use Twig\Extension\AbstractExtension;

/**
 * Class TodoManagerExtension
 *
 * Extension for providing todo manager methods
 *
 * @package App\Twig
 */
class TodoManagerExtension extends AbstractExtension
{
    private TodoManager $todoManager;

    public function __construct(TodoManager $todoManager)
    {
        $this->todoManager = $todoManager;
    }

    /**
     * Get twig functions from todo manager
     *
     * getTodosCount = getTodosCount in TodoManager
     *
     * @return TwigFunction[] Array of TwigFunction objects
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('getTodosCount', [$this->todoManager, 'getTodosCount'])
        ];
    }

    /**
     * Get twig filters from todo manager
     *
     * @return TwigFilter[] Array of TwigFilter objects
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('format_todo_text', [$this, 'formatTodoText'], ['is_safe' => ['html']])
        ];
    }

    /**
     * Formats todo text for display, including bullet points and highlighting
     *
     * @param string $text The raw todo text
     *
     * @return string The HTML formatted text
     */
    public function formatTodoText(string $text): string
    {
        // convert {text} to <span class="highlighted-text">text</span>
        $formattedText = preg_replace('/{(.*?)}/', '<span class="highlighted-text">$1</span>', $text);
        if ($formattedText == null) {
            return $text;
        }

        // process bullet points and newlines
        $lines = explode("\n", $formattedText);
        $output = '';
        $inList = false;

        foreach ($lines as $line) {
            $trimmedLine = trim($line);
            if (str_starts_with($trimmedLine, '*x ')) {
                if (!$inList) {
                    $output .= '<ul>';
                    $inList = true;
                }
                $listItemContent = substr($trimmedLine, 3); // remove "*x "
                $output .= '<li><span class="strikethrough-red">' . $listItemContent . '</span></li>';
            } elseif (str_starts_with($trimmedLine, '* ')) {
                if (!$inList) {
                    $output .= '<ul>';
                    $inList = true;
                }
                $listItemContent = substr($trimmedLine, 2); // remove "* "
                $output .= '<li>' . $listItemContent . '</li>';
            } else {
                if ($inList) {
                    $output .= '</ul>';
                    $inList = false;
                }
                $output .= '<p>' . $trimmedLine . '</p>';
            }
        }

        if ($inList) {
            $output .= '</ul>';
        }

        return $output;
    }
}
