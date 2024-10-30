<?php

namespace App\Util;

use SplFileInfo;
use App\Manager\LogManager;
use App\Manager\ErrorManager;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class FileSystemUtil
 *
 * This class is responsible for handling filesystem related operations
 *
 * @package App\Util
 */
class FileSystemUtil
{
    private LogManager $logManager;
    private ErrorManager $errorManager;

    public function __construct(LogManager $logManager, ErrorManager $errorManager)
    {
        $this->logManager = $logManager;
        $this->errorManager = $errorManager;
    }

    /**
     * Returns a list of files and directories in the specified path
     *
     * @param string $path The path to list files and directories
     *
     * @return array<array<mixed>> The list of files and directories
     */
    public function getFilesList(string $path): array
    {
        $files = [];

        try {
            $finder = new \Symfony\Component\Finder\Finder();
            $finder->in($path)->depth('== 0')->ignoreDotFiles(false)->ignoreVCS(false);

            // get file list as array
            $list = iterator_to_array($finder, false);

            // sort file list
            usort($list, function (SplFileInfo $a, SplFileInfo $b) {
                // sort directories first
                if ($a->isDir() && !$b->isDir()) {
                    return -1;
                } elseif (!$a->isDir() && $b->isDir()) {
                    return 1;
                }

                // sort by filename
                return strcasecmp($a->getFilename(), $b->getFilename());
            });

            // loop through the files and directories
            foreach ($list as $file) {
                $files[] = [
                    'name' => str_replace('/', '', $file->getFilename()),
                    'size' => $file->getSize(),
                    'permissions' => substr(sprintf('%o', $file->getPerms()), -4),
                    'isDir' => $file->isDir(),
                    'path' => $file->getRealPath(),
                ];
            }
        } catch (\Exception $e) {
            // handle the error
            $this->errorManager->handleError(
                message: 'error listing files: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        return $files;
    }

    /**
     * Checks if the file is executable
     *
     * @param string $path The path to the file
     *
     * @return bool True if the file is executable, false otherwise
     */
    public function isFileExecutable(string $path): bool
    {
        // check if file is bash script
        if (str_ends_with($path, '.sh') || str_ends_with($path, '.bash')) {
            return false;
        }

        // check file exists
        if (!file_exists($path)) {
            return false;
        }

        // check if path is directory
        if (is_dir($path) || is_link($path)) {
            return false;
        }

        // get file info
        $fileInfo = exec('sudo file ' . $path);

        // check file info is set
        if (!$fileInfo) {
            // handle the error
            $this->errorManager->handleError(
                message: 'error get file info: ' . $path . ' file info detection failed',
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        // check if the file type is supported
        if (strpos($fileInfo, 'executable')) {
            return true;
        }

        return false;
    }

    /**
     * Detects the media type of a file
     *
     * @param string $path The path to the file
     *
     * @return string The media type of the file
     */
    public function detectMediaType(string $path): string
    {
        // check if file exists
        if (!file_exists($path)) {
            return 'non-mediafile';
        }

        // check if path is a directory or symbolic link
        if (is_dir($path) || is_link($path)) {
            return 'non-mediafile';
        }

        // get file MIME type
        $mimeType = mime_content_type($path);

        // check if MIME type is detected
        if (!$mimeType) {
            // handle error if MIME type detection fails
            $this->errorManager->handleError(
                message: 'Error: Unable to detect MIME type for ' . $path,
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        // determine if file is an image, video, or audio
        if (str_starts_with($mimeType, 'image/')) {
            return $mimeType;
        } elseif (str_starts_with($mimeType, 'video/')) {
            return $mimeType;
        } elseif (str_starts_with($mimeType, 'audio/')) {
            return $mimeType;
        }

        return 'non-mediafile';
    }

    /**
     * Returns the contents of a file
     *
     * @param string $path The path to the file
     *
     * @return string|null The file content or null if the file does not exist
     */
    public function getFileContent(string $path): ?string
    {
        // check file exists
        if (!file_exists($path)) {
            // handle the error
            $this->errorManager->handleError(
                message: 'error opening file: ' . $path . ' does not exist',
                code: Response::HTTP_NOT_FOUND
            );
        }

        try {
            // check if path is directory
            if (is_dir($path) || is_link($path)) {
                // handle the error
                $this->errorManager->handleError(
                    message: 'error opening file: ' . $path . ' is a directory or a link',
                    code: Response::HTTP_BAD_REQUEST
                );
            }

            // get the file content
            $fileContent = shell_exec('sudo cat ' . escapeshellarg($path));

            // check file content is set
            if (!$fileContent) {
                // handle the error
                $this->errorManager->handleError(
                    message: 'error opening file: ' . $path,
                    code: Response::HTTP_INTERNAL_SERVER_ERROR
                );
            }

            // log file access
            $this->logManager->log(
                name: 'file-browser',
                message: 'file ' . $path . ' accessed',
                level: LogManager::LEVEL_INFO
            );

            // return the file content
            return $fileContent;
        } catch (\Exception $e) {
            // handle the error
            $this->errorManager->handleError(
                message: 'error opening file: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
