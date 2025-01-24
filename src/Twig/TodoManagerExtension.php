<?php

namespace App\Twig;

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
}
