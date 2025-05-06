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
     * Check if file exists
     *
     * @param string $path The path to the file
     *
     * @return bool True if the file exists, false otherwise
     */
    public function checkIfFileExist(string $path): bool
    {
        // use shell to check if file exists
        $escapedPath = escapeshellarg($path);
        $cmd = "sudo test -e $escapedPath";

        // check exit status of the shell command
        exec($cmd, $output, $exitCode);
        return $exitCode === 0;
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
            // skip system directories that might cause permission issues
            if (in_array($path, ['/proc', '/sys', '/dev', '/run'])) {
                return [];
            }

            // execute find command with exclusions for system directories
            $type = $recursive ? '-type f' : '\\( -type f -o -type d \\)';
            $depth = $recursive ? '' : '-mindepth 1 -maxdepth 1';
            $excludes = '-not -path "/proc/*" -not -path "/sys/*" -not -path "/dev/*" -not -path "/run/*"';
            $command = "sudo find " . escapeshellarg($path) . " $depth $type $excludes -printf '%f;%s;%m;%y;%p;%T@;%Y\n' 2>/dev/null";
            $output = shell_exec($command);

            // check if output is empty or not set
            if ($output === null) {
                return [];
            }

            // check if output is empty
            if ($output === false || trim($output) === '') {
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
                $parts = explode(';', $line);

                // check if we have all the expected parts
                if (count($parts) < 6) {
                    // skip lines with permission denied or no such file errors
                    if (str_contains($line, 'Permission denied') || str_contains($line, 'No such file or directory')) {
                        continue;
                    }

                    // lLog other problematic lines
                    $this->errorManager->logError(
                        message: 'Invalid format in find output: ' . $line,
                        code: Response::HTTP_INTERNAL_SERVER_ERROR
                    );
                    continue;
                }

                [$name, $size, $permissions, $type, $realPath, $creationTime] = $parts;

                // exclude root and boot directories and original path
                if ($realPath === '/' || $realPath === '/boot' || $realPath === realpath($path)) {
                    continue;
                }

                // for directories, calculate total size if not recursive
                $isDir = $type === 'd';
                $fileSize = (int)$size;

                // if it is a directory and we are not in recursive mode, calculate the total size
                if ($isDir && !$recursive) {
                    $fileSize = $this->calculateDirectorySize($realPath);
                }

                // format size for display
                $formattedSize = $this->formatFileSize($fileSize);

                $files[] = [
                    'name' => $name,
                    'size' => $formattedSize,
                    'rawSize' => $fileSize, // keep raw size for sorting
                    'permissions' => $permissions,
                    'isDir' => $isDir,
                    'path' => $realPath,
                    'creationTime' => date('Y-m-d H:i:s', (int)$creationTime),
                ];
            }

            // sort the list - directories first, then by name
            usort($files, function ($a, $b) {
                // directories always come first
                if ($a['isDir'] && !$b['isDir']) {
                    return -1;
                } elseif (!$a['isDir'] && $b['isDir']) {
                    return 1;
                }

                // if both are directories or both are files, sort by name
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
        // check file exists
        if (!file_exists($path)) {
            return false;
        }

        // check if path is directory
        if (is_dir($path) || is_link($path)) {
            return false;
        }

        // check if file is a log file (by extension or name)
        $fileName = basename($path);
        if (str_ends_with($fileName, '.log') || str_contains($fileName, 'log') || str_contains($fileName, 'exception') || str_contains($path, '/log/')) {
            return false;
        }

        // check if file has executable permissions
        $perms = fileperms($path);
        if ($perms !== false && ($perms & 0111)) {
            return true;
        }

        // get file info
        $fileInfo = exec('sudo file ' . escapeshellarg($path));

        // check file info is set
        if (!$fileInfo) {
            $this->errorManager->handleError(
                message: 'error get file info: ' . $path . ' file info detection failed',
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        // check if file is a log file (by content detection)
        if (strpos($fileInfo, 'text') !== false && (strpos($fileInfo, 'log') !== false || strpos($fileInfo, 'ASCII text') !== false)) {
            return false;
        }

        // check if file is a shell script
        if (strpos($fileInfo, 'shell script') !== false) {
            return true;
        }

        // check if file type is executable
        if (strpos($fileInfo, 'executable') !== false) {
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
     * @param int|null $maxLines Maximum number of lines to read (null for all lines)
     * @param int|null $startLine Line to start reading from (1-based, null for beginning)
     *
     * @return array{content: string, totalLines: int, readLines: int, isTruncated: bool, fileSize: int, readSize: int} Array with file content and metadata
     */
    public function getFileContent(string $path, ?int $maxLines = null, ?int $startLine = null): array
    {
        try {
            // check if path is directory
            if (is_dir($path) || is_link($path)) {
                $this->errorManager->handleError(
                    message: 'error opening file: ' . $path . ' is a directory or a link',
                    code: Response::HTTP_BAD_REQUEST
                );
            }

            // get file size
            $cmd = 'sudo /usr/bin/stat -c %s ' . escapeshellarg($path);
            $fileSize = (int) shell_exec($cmd);

            if ($fileSize == false) {
                $fileSize = 0;
            }

            // default max lines if not specified (adjust as needed)
            $defaultMaxLines = 1000;
            if ($maxLines === null) {
                $maxLines = $defaultMaxLines;
            }

            // default start line if not specified
            if ($startLine === null) {
                $startLine = 1;
            }

            // get total line count without loading the entire file
            $totalLinesOutput = shell_exec('sudo wc -l ' . escapeshellarg($path));

            // parse output to get just the number
            if ($totalLinesOutput !== null && $totalLinesOutput !== false && preg_match('/^\s*(\d+)/', $totalLinesOutput, $matches)) {
                $totalLines = (int)$matches[1];
            } else {
                // fallback if wc command fails
                $this->errorManager->logError(
                    message: 'failed to get line count for file: ' . $path,
                    code: Response::HTTP_INTERNAL_SERVER_ERROR
                );
                $totalLines = 0;
            }

            // if file is too large (over 10MB) or has too many lines, use head/tail with sed
            if ($fileSize > 10 * 1024 * 1024 || $totalLines > $defaultMaxLines) {
                // alculate end line
                $endLine = $startLine + $maxLines - 1;

                // use sed to extract the specified range of lines
                if ($startLine <= 1) {
                    // if starting from the beginning, use head for better performance
                    $command = 'sudo head -n ' . $maxLines . ' ' . escapeshellarg($path);
                } elseif ($startLine > $totalLines - $maxLines) {
                    // if near the end, use tail for better performance
                    $linesToTake = $totalLines - $startLine + 1;
                    $command = 'sudo tail -n ' . $linesToTake . ' ' . escapeshellarg($path);
                } else {
                    // use sed to extract lines from the middle
                    $command = 'sudo sed -n \'' . $startLine . ',' . $endLine . 'p\' ' . escapeshellarg($path);
                }

                $fileContent = shell_exec($command);

                // check if content was retrieved
                if ($fileContent === null || $fileContent === false) {
                    $fileContent = '';
                }

                // calculate how many lines were actually read
                $readLines = min($maxLines, $totalLines - $startLine + 1);
                if ($readLines < 0) {
                    $readLines = 0;
                }

                // calculate approximate read size based on content length
                $readSize = strlen($fileContent);

                return [
                    'content' => $fileContent,
                    'totalLines' => $totalLines,
                    'readLines' => $readLines,
                    'isTruncated' => $readLines < $totalLines,
                    'fileSize' => $fileSize,
                    'readSize' => $readSize
                ];
            } else {
                // for smaller files, read the entire content
                $fileContent = shell_exec('sudo cat ' . escapeshellarg($path));

                // check if content was retrieved
                if ($fileContent === null || $fileContent === false) {
                    $fileContent = '';
                    $totalLines = 0;
                }

                return [
                    'content' => $fileContent,
                    'totalLines' => $totalLines,
                    'readLines' => $totalLines,
                    'isTruncated' => false,
                    'fileSize' => $fileSize,
                    'readSize' => $fileSize
                ];
            }
        } catch (Exception $e) {
            // log error to exception log
            $this->errorManager->logError(
                message: 'error to get file content: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );

            // return error with metadata
            return [
                'content' => $e->getMessage(),
                'totalLines' => 0,
                'readLines' => 0,
                'isTruncated' => false,
                'fileSize' => 0,
                'readSize' => 0
            ];
        }
    }

    /**
     * Save content to file
     *
     * @param string $path The path to the file
     * @param string $content The content to save
     *
     * @return bool True if the content was saved successfully, false otherwise
     */
    public function saveFileContent(string $path, string $content): bool
    {
        try {
            // check if path is directory
            if (is_dir($path) || is_link($path)) {
                $this->errorManager->handleError(
                    message: 'error saving file: ' . $path . ' is a directory or a link',
                    code: Response::HTTP_BAD_REQUEST
                );
            }

            // create temporary file
            $tempFile = tempnam(sys_get_temp_dir(), 'admin_suite_');
            if ($tempFile === false) {
                throw new Exception('Failed to create temporary file');
            }

            // check if file is a shell script
            $isShellScript = false;
            $fileInfo = exec('sudo file ' . escapeshellarg($path));
            if ($fileInfo === false) {
                $fileInfo = '';
            }
            if (file_exists($path) && (strpos($fileInfo, 'shell script') !== false || str_ends_with($path, '.sh') || str_ends_with($path, '.bash'))) {
                $isShellScript = true;
            }

            // fet original file permissions and owner
            $originalPerms = null;
            $fileOwner = null;
            $fileGroup = null;
            if (file_exists($path)) {
                $originalPerms = fileperms($path);
                $fileInfo = stat($path);
                if ($fileInfo !== false) {
                    $fileOwner = $fileInfo['uid'];
                    $fileGroup = $fileInfo['gid'];
                }
            }

            // decode HTML entities in content
            $content = html_entity_decode($content, ENT_QUOTES | ENT_HTML5);

            // for shell scripts, ensure we use LF line endings
            if ($isShellScript) {
                // convert all line endings to LF
                $content = str_replace("\r\n", "\n", $content);
                $content = str_replace("\r", "\n", $content);

                // ensure first line has shebang if it's a shell script
                if (!empty($content) && !preg_match('/^#!/', $content)) {
                    // add shebang if it doesn't exist
                    $content = "#!/bin/bash\n" . $content;
                }
            }

            // ensure content ends with a newline character
            if (!empty($content) && substr($content, -1) !== "\n") {
                $content .= "\n";
            }

            // write content to temporary file
            if (file_put_contents($tempFile, $content) === false) {
                throw new Exception('Failed to write to temporary file');
            }

            // use cat to preserve line endings instead of tee
            $command = 'sudo cat ' . escapeshellarg($tempFile) . ' > ' . escapeshellarg($path);
            $output = shell_exec('sudo bash -c ' . escapeshellarg($command) . ' 2>&1');

            // check if command was successful
            if ($output !== null && !empty($output)) {
                throw new Exception('Failed to save file: ' . $output);
            }

            // restore original permissions if it was executable
            if ($originalPerms !== null && ($originalPerms & 0111)) {
                $chmodCommand = 'sudo chmod ' . sprintf('%o', $originalPerms & 0777) . ' ' . escapeshellarg($path);
                shell_exec($chmodCommand);
            } elseif ($isShellScript) {
                // make shell scripts executable
                $chmodCommand = 'sudo chmod +x ' . escapeshellarg($path);
                shell_exec($chmodCommand);
            }

            // restore original owner and group
            if ($fileOwner !== null && $fileGroup !== null) {
                $chownCommand = 'sudo chown ' . $fileOwner . ':' . $fileGroup . ' ' . escapeshellarg($path);
                shell_exec($chownCommand);
            }

            // remove temporary file
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }

            return true;
        } catch (Exception $e) {
            // log error to exception log
            $this->errorManager->logError(
                message: 'error to save file content: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );

            return false;
        }
    }

    /**
     * Check if file is editable (text file)
     *
     * @param string $path The path to the file
     *
     * @return bool True if the file is editable, false otherwise
     */
    public function isFileEditable(string $path): bool
    {
        // check if file exists
        if (!file_exists($path)) {
            return false;
        }

        // check if path is directory or link
        if (is_dir($path) || is_link($path)) {
            return false;
        }

        // special case for shell scripts - allow editing
        $fileInfo = exec('sudo file ' . escapeshellarg($path));
        if ($fileInfo === false) {
            return false;
        }
        if (strpos($fileInfo, 'shell script') !== false || str_ends_with($path, '.sh') || str_ends_with($path, '.bash')) {
            return true;
        }

        // check if file is executable (but not a shell script)
        if ($this->isFileExecutable($path)) {
            return false;
        }

        // get MIME type using the file command
        $mimeType = shell_exec("sudo file --mime-type -b " . escapeshellarg($path));

        // check if MIME type is detected
        if (!$mimeType) {
            return false;
        }

        // trim output
        $mimeType = trim($mimeType);

        // check if file is a media file
        if (
            str_starts_with($mimeType, 'image/') ||
            str_starts_with($mimeType, 'video/') ||
            str_starts_with($mimeType, 'audio/')
        ) {
            return false;
        }

        // check if file is a binary file
        if (
            str_starts_with($mimeType, 'application/') &&
            !str_contains($mimeType, 'text') &&
            !str_contains($mimeType, 'json') &&
            !str_contains($mimeType, 'xml')
        ) {
            return false;
        }

        return true;
    }

    /**
     * Delete file or directory
     *
     * @param string $path The path to the file or directory to delete
     *
     * @return bool True if the file or directory was deleted successfully, false otherwise
     */
    public function deleteFileOrDirectory(string $path): bool
    {
        try {
            // check if path exists
            if (!file_exists($path)) {
                $this->errorManager->handleError(
                    message: 'error deleting file: ' . $path . ' does not exist',
                    code: Response::HTTP_BAD_REQUEST
                );
            }

            // check if path is a directory
            if (is_dir($path)) {
                // always use rm -rf for directories (empty or not)
                $command = 'sudo rm -rf ' . escapeshellarg($path);
            } else {
                // delete file using sudo
                $command = 'sudo rm ' . escapeshellarg($path);
            }

            // execute command
            $output = shell_exec($command);

            // check if command was successful
            if ($output !== null && !empty($output)) {
                throw new Exception('Failed to delete file or directory: ' . $output);
            }

            return true;
        } catch (Exception $e) {
            // log error to exception log
            $this->errorManager->logError(
                message: 'error to delete file or directory: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );

            return false;
        }
    }

    /**
     * Rename file or directory
     *
     * @param string $oldPath The path to the file or directory to rename
     * @param string $newPath The new full path for the file or directory
     *
     * @return bool True if the file or directory was renamed successfully, false otherwise
     */
    public function renameFileOrDirectory(string $oldPath, string $newPath): bool
    {
        try {
            // check if old path exists
            if (!file_exists($oldPath)) {
                $this->errorManager->handleError(
                    message: 'error renaming file: ' . $oldPath . ' does not exist',
                    code: Response::HTTP_BAD_REQUEST
                );
            }

            // check if new path already exists
            if (file_exists($newPath)) {
                throw new Exception('Destination already exists: ' . $newPath);
            }

            // rename file or directory using sudo
            $command = 'sudo mv ' . escapeshellarg($oldPath) . ' ' . escapeshellarg($newPath);
            $output = shell_exec($command);

            // check if command was successful
            if ($output !== null && !empty($output)) {
                throw new Exception('Failed to rename file or directory: ' . $output);
            }

            // Verify the rename was successful
            if (!file_exists($newPath)) {
                throw new Exception('Rename operation completed but the new path does not exist: ' . $newPath);
            }

            return true;
        } catch (Exception $e) {
            // log error to exception log
            $this->errorManager->logError(
                message: 'error to rename file or directory: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );

            return false;
        }
    }

    /**
     * Create directory
     *
     * @param string $path The path to the directory to create
     *
     * @return bool True if the directory was created successfully, false otherwise
     */
    public function createDirectory(string $path): bool
    {
        try {
            // check if path already exists
            if (file_exists($path)) {
                $this->errorManager->handleError(
                    message: 'error creating directory: ' . $path . ' already exists',
                    code: Response::HTTP_BAD_REQUEST
                );
            }

            // create directory using sudo
            $command = 'sudo mkdir -p ' . escapeshellarg($path);
            $output = shell_exec($command);

            // check if command was successful
            if ($output !== null && !empty($output)) {
                throw new Exception('Failed to create directory: ' . $output);
            }

            return true;
        } catch (Exception $e) {
            // log error to exception log
            $this->errorManager->logError(
                message: 'error to create directory: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );

            return false;
        }
    }
    /**
     * Get full content of file without pagination for editing
     *
     * @param string $path The path to the file
     *
     * @return string The file content or error message
     */
    public function getFullFileContent(string $path): string
    {
        try {
            // check if path is directory
            if (is_dir($path) || is_link($path)) {
                $this->errorManager->handleError(
                    message: 'error opening file: ' . $path . ' is a directory or a link',
                    code: Response::HTTP_BAD_REQUEST
                );
            }

            // get file content using cat
            $fileContent = shell_exec('sudo cat ' . escapeshellarg($path));

            // check if content was retrieved
            if ($fileContent === null || $fileContent === false) {
                return '';
            }

            return $fileContent;
        } catch (Exception $e) {
            // log error to exception log
            $this->errorManager->logError(
                message: 'error to get file content: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );

            // return error message
            return $e->getMessage();
        }
    }

    /**
     * Calculate the total size of a directory including all files and subdirectories
     *
     * @param string $path The path to the directory
     *
     * @return int The total size in bytes
     */
    public function calculateDirectorySize(string $path): int
    {
        try {
            // check if path exists and is a directory
            if (!file_exists($path) || !is_dir($path)) {
                return 0;
            }

            // use du command to get directory size
            $command = 'sudo du -sb ' . escapeshellarg($path) . ' 2>&1';
            $output = shell_exec($command);

            // check if output is empty or not set
            if ($output === null || $output === false) {
                return 0;
            }

            // parse output to get the size
            if (preg_match('/^(\d+)\s+/', $output, $matches)) {
                return (int)$matches[1];
            }

            return 0;
        } catch (Exception $e) {
            $this->errorManager->logError(
                message: 'Error calculating directory size: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
            return 0;
        }
    }

    /**
     * Format file size to human-readable format
     *
     * @param int $bytes The size in bytes
     * @param int $precision The number of decimal places to round to
     *
     * @return string The formatted size
     */
    public function formatFileSize(int $bytes, int $precision = 2): string
    {
        if ($bytes <= 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
        $base = 1024;
        $exponent = floor(log($bytes, $base));
        $value = $bytes / pow($base, $exponent);

        return round($value, $precision) . ' ' . $units[$exponent];
    }

    /**
     * Move file or directory to a new location
     *
     * @param string $sourcePath The source path of the file or directory
     * @param string $destinationPath The destination directory path
     *
     * @return bool True if the file or directory was moved successfully, false otherwise
     */
    public function moveFileOrDirectory(string $sourcePath, string $destinationPath): bool
    {
        try {
            // check if source path exists
            if (!file_exists($sourcePath)) {
                $this->errorManager->handleError(
                    message: 'Error moving file: ' . $sourcePath . ' does not exist',
                    code: Response::HTTP_BAD_REQUEST
                );
            }

            // check if destination path exists and is a directory
            if (!file_exists($destinationPath) || !is_dir($destinationPath)) {
                $this->errorManager->handleError(
                    message: 'Error moving file: Destination ' . $destinationPath . ' is not a valid directory',
                    code: Response::HTTP_BAD_REQUEST
                );
            }

            // get the basename of the source
            $basename = basename($sourcePath);

            // build the full destination path
            if ($destinationPath === '/') {
                $newPath = '/' . $basename;
            } else {
                $newPath = rtrim($destinationPath, '/') . '/' . $basename;
            }

            // check if destination already exists
            if (file_exists($newPath)) {
                throw new Exception('Destination already exists: ' . $newPath);
            }

            // check if source is a subdirectory of destination (for directories)
            if (is_dir($sourcePath) && strpos($destinationPath, $sourcePath . '/') === 0) {
                throw new Exception('Cannot move a directory into its own subdirectory');
            }

            // move file or directory using sudo
            if ($destinationPath === '/') {
                $command = 'sudo mv ' . escapeshellarg($sourcePath) . ' ' . escapeshellarg('/' . $basename);
            } else {
                $command = 'sudo mv ' . escapeshellarg($sourcePath) . ' ' . escapeshellarg($destinationPath);
            }
            $output = shell_exec($command);

            // check if command was successful
            if ($output !== null && !empty($output)) {
                throw new Exception('Failed to move file or directory: ' . $output);
            }

            // build full destination path for verification
            if ($destinationPath === '/') {
                $newFullPath = '/' . $basename;
            } else {
                $newFullPath = rtrim($destinationPath, '/') . '/' . $basename;
            }

            // verify the move was successful
            if (!file_exists($newFullPath)) {
                throw new Exception('Move operation completed but the new path does not exist: ' . $newFullPath);
            }

            return true;
        } catch (Exception $e) {
            // log error to exception log
            $this->errorManager->logError(
                message: 'Error moving file or directory: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );

            return false;
        }
    }
}
