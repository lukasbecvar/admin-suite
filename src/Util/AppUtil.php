<?php

namespace App\Util;

use Exception;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Class AppUtil
 *
 * The utility class for the application
 *
 * @package App\Util
 */
class AppUtil
{
    private JsonUtil $jsonUtil;
    private KernelInterface $kernelInterface;

    public function __construct(JsonUtil $jsonUtil, KernelInterface $kernelInterface)
    {
        $this->jsonUtil = $jsonUtil;
        $this->kernelInterface = $kernelInterface;
    }

    /** Get the application root directory
     *
     * @return string The application root directory
     */
    public function getAppRootDir(): string
    {
        return $this->kernelInterface->getProjectDir();
    }

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
        return file_exists($this->getAppRootDir() . '/public/assets/');
    }

    /**
     * Check if the application is in development mode
     *
     * @return bool True if the application is in development mode, false otherwise
     */
    public function isDevMode(): bool
    {
        $envName = $this->getEnvValue('APP_ENV');

        if ($envName == 'dev' || $envName == 'test') {
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
        return $this->getEnvValue('SSL_ONLY') === 'true';
    }

    /**
     * Check if the application is in maintenance mode
     *
     * @return bool True if the application is in maintenance mode, false otherwise
     */
    public function isMaintenance(): bool
    {
        return $this->getEnvValue('MAINTENANCE_MODE') === 'true';
    }

    /**
     * Check if the database logging is enabled
     *
     * @return bool True if the database logging is enabled, false otherwise
     */
    public function isDatabaseLoggingEnabled(): bool
    {
        return $this->getEnvValue('DATABASE_LOGGING') === 'true';
    }

    /**
     * Get the environment variable value
     *
     * @param string $key The environment variable key
     *
     * @return string The environment variable value
     */
    public function getEnvValue(string $key): string
    {
        return $_ENV[$key];
    }

    /**
     * Get the hasher configuration
     *
     * @return array<int> The hasher configuration
     */
    public function getHasherConfig(): array
    {
        return [
            'memory_cost' => (int) $this->getEnvValue('MEMORY_COST'),
            'time_cost' => (int) $this->getEnvValue('TIME_COST'),
            'threads' => (int) $this->getEnvValue('THREADS')
        ];
    }

    /**
     * Load config file
     *
     * @param string $configFile The config file to load
     *
     * @return array<mixed>|null The config file content, null if the file does not exist
     */
    public function loadConfig(string $configFile): ?array
    {
        // default example config path
        $configPath = $this->getAppRootDir() . '/config/suite/' . $configFile;

        // set config path
        if (file_exists($this->getAppRootDir() . '/' . $configFile)) {
            $configPath = $this->getAppRootDir() . '/' . $configFile;
        }

        // load config file
        $config = $this->jsonUtil->getJson($configPath);

        return $config;
    }

    /**
     * Calculate the maximum number of pages
     *
     * @param ?int $totalItems The total number of items
     * @param ?int $itemsPerPage The number of items per page
     *
     * @return int|float The maximum number of pages
     */
    public function calculateMaxPages(?int $totalItems, ?int $itemsPerPage): int|float
    {
        // validate the inputs to make sure they are positive integers
        if ($totalItems <= 0 || $itemsPerPage <= 0) {
            return 0;
        }

        // calculate the maximum number of pages
        $maxPages = ceil($totalItems / $itemsPerPage);

        // return the maximum number of pages
        return $maxPages;
    }

    /**
     * Get config from yaml file
     *
     * @param string $configFile The config file name
     *
     * @return mixed The config data
     */
    public function getYamlConfig(string $configFile): mixed
    {
        return Yaml::parseFile($this->getAppRootDir() . '/config/' . $configFile);
    }

    /**
     * Update the environment variable value
     *
     * @param string $key The environment variable key
     * @param string $value The environment variable value
     *
     * @throws Exception If the environment file is not found or the environment variable is not found
     */
    public function updateEnvValue(string $key, string $value): void
    {
        // get base .env file
        $mainEnvFile = $this->getAppRootDir() . '/.env';

        // chec if .env file exists
        if (!file_exists($mainEnvFile)) {
            throw new Exception('.env file not found');
        }

        // load base .env file content
        $mainEnvContent = file_get_contents($mainEnvFile);
        if ($mainEnvContent === false) {
            throw new Exception('Failed to read .env file');
        }

        // load current environment name
        if (preg_match('/^APP_ENV=(\w+)$/m', $mainEnvContent, $matches)) {
            $env = $matches[1];
        } else {
            throw new Exception('APP_ENV not found in .env file');
        }

        // get current environment file
        $envFile = $this->getAppRootDir() . '/.env.' . $env;

        // check if current environment file exists
        if (!file_exists($envFile)) {
            throw new Exception(".env.$env file not found");
        }

        // get current environment content
        $envContent = file_get_contents($envFile);

        // check if current environment loaded correctly
        if ($envContent === false) {
            throw new Exception("Failed to read .env.$env file");
        }

        try {
            if (preg_match('/^' . $key . '=.*/m', $envContent, $matches)) {
                $newEnvContent = preg_replace('/^' . $key . '=.*/m', "$key=$value", $envContent);

                // write new content to the environment file
                if (file_put_contents($envFile, $newEnvContent) === false) {
                    throw new Exception('Failed to write to .env ' . $env . ' file');
                }
            } else {
                throw new Exception($key . ' not found in .env file');
            }
        } catch (Exception $e) {
            throw new Exception('Error to update environment variable: ' . $e->getMessage());
        }
    }
}
