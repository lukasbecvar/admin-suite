<?php

namespace App\Manager;

use App\Util\AppUtil;
use App\Util\FileSystemUtil;

/**
 * Class ConfigManager
 *
 * Manager for configuration system
 *
 * @package App\Manager
 */
class ConfigManager
{
    private AppUtil $appUtil;
    private LogManager $logManager;
    private FileSystemUtil $fileSystemUtil;

    public function __construct(AppUtil $appUtil, LogManager $logManager, FileSystemUtil $fileSystemUtil)
    {
        $this->appUtil = $appUtil;
        $this->logManager = $logManager;
        $this->fileSystemUtil = $fileSystemUtil;
    }

    /**
     * Get list of suite configuration files
     *
     * @return list<array{filename: string, is_custom: bool}> List of suite configuration files
     */
    public function getSuiteConfigs(): array
    {
        // path to suite configuration files
        $defaultConfigPath = $this->appUtil->getAppRootDir() . '/config/suite';

        // get list of files in suite configuration directory
        $files = $this->fileSystemUtil->getFilesList($defaultConfigPath);
        $configs = [];
        foreach ($files as $file) {
            /** @var string $filename */
            $filename = $file['name'];
            $isCustom = $this->isCustomConfig($filename);
            $configs[] = [
                'filename' => $filename,
                'is_custom' => $isCustom,
            ];
        }

        return $configs;
    }

    /**
     * Get content of specific suite configuration file
     *
     * @param string $filename The filename of the configuration file
     *
     * @return string|null The content of the configuration file or null if file not found
     */
    public function readConfig(string $filename): ?string
    {
        // build config file paths
        $customPath = $this->appUtil->getAppRootDir() . '/' . $filename;
        $defaultPath = $this->appUtil->getAppRootDir() . '/config/suite/' . $filename;

        // check if custom config file exists
        $path = $this->fileSystemUtil->checkIfFileExist($customPath) ? $customPath : $defaultPath;

        // check if config file exists
        if (!$this->fileSystemUtil->checkIfFileExist($path)) {
            return null;
        }

        // read config file content
        return $this->fileSystemUtil->getFullFileContent($path);
    }

    /**
     * Write content to specific suite configuration file (write to custom config path)
     *
     * @param string $filename The filename of the configuration file
     * @param string $content The content to write to the configuration file
     *
     * @return bool True if write operation was successful, false otherwise
     */
    public function writeConfig(string $filename, string $content): bool
    {
        // build path to custom config file
        $path = $this->appUtil->getAppRootDir() . '/' . $filename;

        // rewrite custom config file content
        $result = $this->fileSystemUtil->saveFileContent($path, $content);

        // check if write operation was successful
        if ($result) {
            $this->logManager->log(
                name: 'suite-config',
                message: 'Updated config file: ' . $filename,
                level: LogManager::LEVEL_INFO
            );
        }

        return $result;
    }

    /**
     * Copy specific suite configuration file to root directory
     *
     * @param string $filename The filename of the configuration file
     *
     * @return bool True if copy operation was successful, false otherwise
     */
    public function copyConfigToRoot(string $filename): bool
    {
        $sourcePath = $this->appUtil->getAppRootDir() . '/config/suite/' . $filename;
        $destinationPath = $this->appUtil->getAppRootDir() . '/' . $filename;

        // check if source file exists and destination file does not
        if ($this->fileSystemUtil->checkIfFileExist($sourcePath) && !$this->fileSystemUtil->checkIfFileExist($destinationPath)) {
            // get default config file content
            $content = $this->fileSystemUtil->getFullFileContent($sourcePath);

            // create custom config file
            $result = $this->fileSystemUtil->saveFileContent($destinationPath, $content);

            // check if write operation was successful
            if ($result) {
                $this->logManager->log(
                    name: 'suite-config',
                    message: 'Created custom config file: ' . $filename,
                    level: LogManager::LEVEL_INFO
                );
            }
            return $result;
        }

        return false;
    }

    /**
     * Check if specific suite configuration file is a custom config file
     *
     * @param string $filename The filename of the configuration file
     *
     * @return bool True if the file is a custom config file, false otherwise
     */
    public function isCustomConfig(string $filename): bool
    {
        return $this->fileSystemUtil->checkIfFileExist($this->appUtil->getAppRootDir() . '/' . $filename);
    }

    /**
     * Delete specific suite configuration file (reset to default)
     *
     * @param string $filename The filename of the configuration file
     *
     * @return bool True if delete operation was successful, false otherwise
     */
    public function deleteConfig(string $filename): bool
    {
        $path = $this->appUtil->getAppRootDir() . '/' . $filename;

        // check if file exists
        if ($this->fileSystemUtil->checkIfFileExist($path)) {
            // delete custom config file
            $result = $this->fileSystemUtil->deleteFileOrDirectory($path);
            if ($result) {
                $this->logManager->log(
                    name: 'suite-config',
                    message: 'Deleted custom config file: ' . $filename,
                    level: LogManager::LEVEL_WARNING
                );
            }
            return $result;
        }

        return false;
    }
}
