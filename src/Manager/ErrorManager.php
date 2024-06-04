<?php

namespace App\Manager;

use Twig\Environment;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Class ErrorManager
 *
 * The manager for handling errors
 *
 * @package App\Manager
 */
class ErrorManager
{
    private Environment $twig;

    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
    }

    /**
     * Handle an error
     *
     * @param string $message The error message
     * @param int $code The error code
     *
     * @return void
     */
    public function handleError(string $message, int $code): void
    {
        throw new HttpException($code, $message, null, [], $code);
    }

    /**
     * Get the error view
     *
     * @param string|int $code The error code
     *
     * @throws \Exception If the error view does not exist
     *
     * @return string The error view
     */
    public function getErrorView(string|int $code): string
    {
        try {
            return $this->twig->render('error/error-' . $code . '.html.twig');
        } catch (\Exception) {
            return $this->twig->render('error/error-unknown.html.twig');
        }
    }
}
