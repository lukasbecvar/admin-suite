<?php

namespace App\Exception;

use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Class AppErrorException
 *
 * Exception for application errors
 *
 * @package App\Exception
 */
class AppErrorException extends HttpException
{
    /**
     * The AppErrorException constructor
     *
     * @param int $code The error code
     * @param string $message The error message
     * @param \Throwable|null $previous The previous exception
     * @param array<string> $headers The headers
     */
    public function __construct(int $code, string $message, \Throwable $previous = null, array $headers = [])
    {
        parent::__construct($code, $message, $previous, $headers);
    }
}
