<?php

namespace App\Util;

use Exception;
use InvalidArgumentException;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Class AppUtil
 *
 * Util with basic app, env & config methods
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

    /**
     * Generate random key
     *
     * @param int $length The key length
     *
     * @return string The generated key
     */
    public function generateKey(int $length = 16): string
    {
        // check if length is valid
        if ($length < 1) {
            throw new InvalidArgumentException('Length must be greater than 0.');
        }

        return bin2hex(random_bytes($length));
    }

    /**
     * Get application root directory
     *
     * @return string The application root directory
     */
    public function getAppRootDir(): string
    {
        return $this->kernelInterface->getProjectDir();
    }

    /**
     * Check if request is running over SSL
     *
     * @return bool True if request is secured via SSL, false otherwise
     */
    public function isSsl(): bool
    {
        // check if HTTPS header is set
        return isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] == 1 || strtolower($_SERVER['HTTPS']) === 'on');
    }

    /**
     * Check if assets exist
     *
     * @return bool True if assets exist, false otherwise
     */
    public function isAssetsExist(): bool
    {
        return file_exists($this->getAppRootDir() . '/public/assets/');
    }

    /**
     * Check if application is in development mode
     *
     * @return bool True if application is in development mode, false otherwise
     */
    public function isDevMode(): bool
    {
        // get env name
        $envName = $this->getEnvValue('APP_ENV');
        if ($envName == 'dev' || $envName == 'test') {
            return true;
        }

        return false;
    }

    /**
     * Check if SSL only is enabled
     *
     * @return bool True if SSL only is enabled, false otherwise
     */
    public function isSSLOnly(): bool
    {
        return $this->getEnvValue('SSL_ONLY') === 'true';
    }

    /**
     * Check if application is in maintenance mode
     *
     * @return bool True if application is in maintenance mode, false otherwise
     */
    public function isMaintenance(): bool
    {
        return $this->getEnvValue('MAINTENANCE_MODE') === 'true';
    }

    /**
     * Check if database logging is enabled
     *
     * @return bool True if database logging is enabled, false otherwise
     */
    public function isDatabaseLoggingEnabled(): bool
    {
        return $this->getEnvValue('DATABASE_LOGGING') === 'true';
    }

    /**
     * Get environment variable value
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
     * Get hasher configuration
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
     * Load config file (json files only)
     *
     * @param string $configFile The config file to load
     *
     * @return array<mixed>|null The config file content, null if the file does not exist
     */
    public function loadConfig(string $configFile): ?array
    {
        // path to suite configs folder
        $configPath = $this->getAppRootDir() . '/config/suite/' . $configFile;

        // set config path to specified file
        if (file_exists($this->getAppRootDir() . '/' . $configFile)) {
            $configPath = $this->getAppRootDir() . '/' . $configFile;
        }

        // load config file
        $config = $this->jsonUtil->getJson($configPath);
        return $config;
    }

    /**
     * Calculate maximum number of pages
     *
     * @param ?int $totalItems The total number of items
     * @param ?int $itemsPerPage The number of items per page
     *
     * @return int|float The maximum number of pages
     */
    public function calculateMaxPages(?int $totalItems, ?int $itemsPerPage): int|float
    {
        // validate inputs to make sure they are positive integers
        if ($totalItems <= 0 || $itemsPerPage <= 0) {
            return 0;
        }

        // calculate maximum number of pages
        $maxPages = ceil($totalItems / $itemsPerPage);

        // return maximum number of pages
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
     * Check if feature flag is disabled
     *
     * @param string $featureFlag The feature flag to check
     *
     * @return bool True if feature flag is disabled, false otherwise
     */
    public function isFeatureFlagDisabled(string $featureFlag): bool
    {
        // get feature flags config
        $disabledFeatureFlags = $this->loadConfig('feature-flags.json');

        // check if feature flag is disabled
        return isset($disabledFeatureFlags[$featureFlag]) && $disabledFeatureFlags[$featureFlag] === false;
    }

    /**
     * Update environment variable value
     *
     * @param string $key The environment variable key
     * @param string $value The environment variable value
     *
     * @throws Exception If the environment variable update fails
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

                // write new content to environment file
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

    /**
     * Round times in array
     *
     * @param array<string> $values The array of values
     *
     * @return array<string> The array of rounded values
     */
    public function roundTimesInArray(array $values): array
    {
        return array_map(function (string $value) {
            if (preg_match('/^\d{2}:\d{2}$/', $value)) {
                [$hour, $minute] = explode(':', $value);
                if ($minute >= 30) {
                    $hour = (intval($hour) + 1) % 24;
                }
                return sprintf('%02d:00', $hour);
            }
            if (preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $value)) {
                [$date, $time] = explode(' ', $value);
                [$hour, $minute] = explode(':', $time);
                if ($minute >= 30) {
                    $hour = (intval($hour) + 1) % 24;
                }
                return sprintf('%s %02d:00', $date, $hour);
            }
            return $value;
        }, $values);
    }

    /**
     * Format bytes into human readable format
     *
     * @param int $bytes Number of bytes
     *
     * @return string Formatted string
     */
    public function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $unitIndex = 0;

        while ($bytes >= 1024 && $unitIndex < count($units) - 1) {
            $bytes /= 1024;
            $unitIndex++;
        }

        return round($bytes, 2) . ' ' . $units[$unitIndex];
    }
}
