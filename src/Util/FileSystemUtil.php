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
    private AppUtil $appUtil;
    private ErrorManager $errorManager;

    public function __construct(AppUtil $appUtil, ErrorManager $errorManager)
    {
        $this->appUtil = $appUtil;
        $this->errorManager = $errorManager;
    }

    /**
     * Return list of files and directories in the specified path
     *
     * @param string $path The path to list files and directories
     * @param bool $recursive Spec for log manager (return all files resursive without directories)
     *
     * @throws Exception If an error occurs while listing the files
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
                message: 'Error listing files: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        // return the final list
        return $files;
    }

    /**
     * Check if the file is executable
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
            // handle error
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
     * Get content of a file
     *
     * @param string $path The path to the file
     *
     * @throws Exception If an error occurs while getting the file content
     *
     * @return string|null The file content or null if the file does not exist
     */
    public function getFileContent(string $path): ?string
    {
        // check file exists
        if (!file_exists($path)) {
            // handle error
            $this->errorManager->handleError(
                message: 'error opening file: ' . $path . ' does not exist',
                code: Response::HTTP_NOT_FOUND
            );
        }

        try {
            // check if path is directory
            if (is_dir($path) || is_link($path)) {
                // handle error
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
            // return error to file view in non dev mode
            if (!$this->appUtil->isDevMode()) {
                return $e->getMessage();
            }

            // handle error
            $this->errorManager->handleError(
                message: 'error opening file: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
