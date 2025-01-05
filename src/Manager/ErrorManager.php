<?php

namespace App\Manager;

use Exception;
use Twig\Environment;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Class ErrorManager
 *
 * The manager for error handling
 *
 * @package App\Manager
 */
class ErrorManager
{
    private Environment $twig;
    private LoggerInterface $logger;

    public function __construct(Environment $twig, LoggerInterface $logger)
    {
        $this->twig = $twig;
        $this->logger = $logger;
    }

    /**
     * Handle error exception
     *
     * @param string $message The error message
     * @param int $code The error code
     *
     * @return never Always throws error exception
     */
    public function handleError(string $message, int $code): void
    {
        throw new HttpException($code, $message, null, [], $code);
    }

    /**
     * Get error view
     *
     * @param string|int $code The error code
     *
     * @return string The error view
     */
    public function getErrorView(string|int $code): string
    {
        try {
            return $this->twig->render('error/error-' . $code . '.twig');
        } catch (Exception) {
            return $this->twig->render('error/error-unknown.twig');
        }
    }

    /**
     * Log error to exception log
     *
     * @param string $message The error message
     * @param int $code The error code
     *
     * @return void
     */
    public function logError(string $message, int $code): void
    {
        $this->logger->error($message, ['code' => $code]);
    }
}
