<?php

namespace App\Twig;

use Twig\TwigFunction;
use App\Manager\TodoManager;
use Twig\Extension\AbstractExtension;

/**
 * Class TodoManagerExtension
 *
 * Twig extension for the todo manager
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
     * Get the twig functions
     *
     * @return TwigFunction[] An array of TwigFunction objects
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('getTodosCount', [$this->todoManager, 'getTodosCount']),
        ];
    }
}
