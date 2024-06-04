<?php

namespace App\Util;

/**
 * Class AppUtil
 *
 * The utility class for the application
 *
 * @package App\Util
 */
class AppUtil
{
    /**
     * Check if the request is SSL
     *
     * @return bool True if the request is SSL, false otherwise
     */
    public function isSsl(): bool
    {
        // check if HTTPS header is set and its value is either 1 or 'on'
        return isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] == 1 || strtolower($_SERVER['HTTPS']) === 'on');
    }

    /**
     * Check if the assets exist
     *
     * @return bool True if the assets exist, false otherwise
     */
    public function isAssetsExist(): bool
    {
        return file_exists(__DIR__ . '/../../public/build/');
    }

    /**
     * Check if the application is in development mode
     *
     * @return bool True if the application is in development mode, false otherwise
     */
    public function isDevMode(): bool
    {
        if ($_ENV['APP_ENV'] == 'dev' || $_ENV['APP_ENV'] == 'test') {
            return true;
        }

        return false;
    }

    /**
     * Check if the application is in production mode
     *
     * @return bool True if the application is in production mode, false otherwise
     */
    public function isSSLOnly(): bool
    {
        return $_ENV['SSL_ONLY'] === 'true';
    }

    /**
     * Check if the application is in maintenance mode
     *
     * @return bool True if the application is in maintenance mode, false otherwise
     */
    public function isMaintenance(): bool
    {
        return $_ENV['MAINTENANCE_MODE'] === 'true';
    }

    /**
     * Check if the database logging is enabled
     *
     * @return bool True if the database logging is enabled, false otherwise
     */
    public function isDatabaseLoggingEnabled(): bool
    {
        return $_ENV['DATABASE_LOGGING'] === 'true';
    }

    /**
     * Get the log level
     *
     * @return int The log level
     */
    public function getLogLevel(): int
    {
        return (int) $_ENV['LOG_LEVEL'];
    }
}
