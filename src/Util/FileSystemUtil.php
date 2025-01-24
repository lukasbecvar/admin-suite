<?php

namespace App\Util;

use Exception;
use App\Manager\ErrorManager;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class FileSystemUtil
 *
 * Util for manipulate with the file system
 *
 * @package App\Util
 */
class FileSystemUtil
{
    private ErrorManager $errorManager;

    public function __construct(ErrorManager $errorManager)
    {
        $this->errorManager = $errorManager;
    }

    /**
     * Get list of files and directories in the specified path
     *
     * @param string $path The path to list files and directories
     * @param bool $recursive Spec for log manager (return all files resursive without directories)
     *
     * @return array<array<mixed>> The list of files and directories
     */
    public function getFilesList(string $path, bool $recursive = false): array
    {
        // set default path if is empty
        if (empty($path)) {
            $path = '/';
        }

        $files = [];

        try {
            // execute find command
            $type = $recursive ? '-type f' : '\\( -type f -o -type d \\)';
            $depth = $recursive ? '' : '-mindepth 1 -maxdepth 1';
            $command = "sudo find " . escapeshellarg($path) . " $depth $type -printf '%f;%s;%m;%y;%p\n' 2>&1";
            $output = shell_exec($command);

            // check if output is empty
            if ($output == false || trim($output) === '') {
                // return empty array if no files found
                return $files;
            }

            // split output to lines
            $lines = explode("\n", trim($output));
            foreach ($lines as $line) {
                // skip empty lines
                if (empty(trim($line))) {
                    continue;
                }

                // split output to variables
                [$name, $size, $permissions, $type, $realPath] = explode(';', $line);

                // exclude root and boot directories and original path
                if ($realPath === '/' || $realPath === '/boot' || $realPath === realpath($path)) {
                    continue;
                }

                $files[] = [
                    'name' => $name,
                    'size' => (int)$size,
                    'permissions' => $permissions,
                    'isDir' => $type === 'd',
                    'path' => $realPath,
                ];
            }

            // sort the list
            usort($files, function ($a, $b) {
                if ($a['isDir'] && !$b['isDir']) {
                    return -1;
                } elseif (!$a['isDir'] && $b['isDir']) {
                    return 1;
                }
                return strcasecmp($a['name'], $b['name']);
            });
        } catch (Exception $e) {
            $this->errorManager->handleError(
                message: 'error listing files: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        // return final list
        return $files;
    }

    /**
     * Check if file is executable
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
            $this->errorManager->handleError(
                message: 'error get file info: ' . $path . ' file info detection failed',
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        // check if file type is supported
        if (strpos($fileInfo, 'executable')) {
            return true;
        }

        return false;
    }

    /**
     * Detect media type of a file
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

        // get MIME type using the file command
        $mimeType = shell_exec("sudo file --mime-type -b " . escapeshellarg($path));

        // check if MIME type is detected
        if (!$mimeType) {
            $this->errorManager->handleError(
                message: 'Error: Unable to detect MIME type for ' . $path,
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        // trim output
        $mimeType = trim($mimeType);

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
     * Get content of file
     *
     * @param string $path The path to the file
     *
     * @return string|null The file content or null if the file does not exist
     */
    public function getFileContent(string $path): ?string
    {
        // check file exists
        if (!file_exists($path)) {
            $this->errorManager->handleError(
                message: 'error opening file: ' . $path . ' does not exist',
                code: Response::HTTP_NOT_FOUND
            );
        }

        try {
            // check if path is directory
            if (is_dir($path) || is_link($path)) {
                $this->errorManager->handleError(
                    message: 'error opening file: ' . $path . ' is a directory or a link',
                    code: Response::HTTP_BAD_REQUEST
                );
            }

            // get file content
            $fileContent = shell_exec('sudo cat ' . escapeshellarg($path));

            // check file content is set
            if (!$fileContent) {
                $fileContent = 'file is empty';
            }

            // return file content
            return $fileContent;
        } catch (Exception $e) {
            // log error to exception log
            $this->errorManager->logError(
                message: 'error to get file content: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );

            // return error to file view
            return $e->getMessage();
        }
    }
}
