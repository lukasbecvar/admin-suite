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
     * Check if file exists
     *
     * @param string $path The path to the file
     *
     * @return bool True if the file exists, false otherwise
     */
    public function checkIfFileExist(string $path): bool
    {
        $sudoPath = $this->appUtil->getSudoPath();

        // use shell to check if file exists
        $escapedPath = escapeshellarg($path);
        $cmd = "$sudoPath test -e $escapedPath";

        // check exit status of the shell command
        exec($cmd, $output, $exitCode);
        return $exitCode === 0;
    }

    /**
     * Check if path is a directory
     *
     * @param string $path The path to check
     *
     * @return bool True if the path is a directory, false otherwise
     */
    public function isPathDirectory(string $path): bool
    {
        $sudoPath = $this->appUtil->getSudoPath();

        // use shell to check if path is a directory
        $escapedPath = escapeshellarg($path);
        $cmd = "$sudoPath test -d $escapedPath";

        // check exit status of the shell command
        exec($cmd, $output, $exitCode);
        return $exitCode === 0;
    }

    /**
     * Check if file is a symlink
     *
     * @param string $path The path to the file
     *
     * @return bool True if the file is a symlink, false otherwise
     */
    public function checkIfFileIsSymlink(string $path): bool
    {
        $sudoPath = $this->appUtil->getSudoPath();

        // use shell to check if path is a symlink
        $escapedPath = escapeshellarg($path);
        $cmd = "$sudoPath test -L $escapedPath";

        // check exit status of the shell command
        exec($cmd, $output, $exitCode);
        return $exitCode === 0;
    }

    /**
     * Get file permissions
     *
     * @param string $path The path to the file
     *
     * @return int|null The file permissions or null on error
     */
    public function getFilePermissions(string $path): ?int
    {
        $sudoPath = $this->appUtil->getSudoPath();
        $escapedPath = escapeshellarg($path);
        $statFormat = $this->appUtil->isHostRunningOnFreeBSD() ? '-f %Lp' : '-c %a';
        $cmd = "$sudoPath stat $statFormat $escapedPath 2>&1";
        $output = [];
        $exitCode = 0;

        exec($cmd, $output, $exitCode);

        // stat failed? → return null
        if ($exitCode !== 0 || empty($output[0]) || !ctype_digit($output[0])) {
            return null;
        }

        // convert octal string → int (cast removes float possibility)
        return (int) octdec($output[0]);
    }

    /**
     * Get list of files and directories in the specified path, with optional pagination
     *
     * @param string $path The path to list files and directories
     * @param bool $recursive Spec for log manager (return all files recursive without directories)
     * @param int|null $page The current page number for pagination
     * @param int|null $limit The number of items per page for pagination
     *
     * @return array<array<mixed>> The list of files and directories
     */
    public function getFilesList(string $path, bool $recursive = false, ?int $page = null, ?int $limit = null): array
    {
        $sudoPath = $this->appUtil->getSudoPath();

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

            // FreeBSD find does not support -printf, use -exec stat instead
            if ($this->appUtil->isHostRunningOnFreeBSD()) {
                $command = "$sudoPath find " . escapeshellarg($path) . " $depth $type $excludes -exec stat -f '%N|%z|%Lp|%HT|%m' {} + 2>/dev/null";
            } else {
                $command = "$sudoPath find " . escapeshellarg($path) . " $depth $type $excludes -printf '%f;%s;%m;%y;%p;%T@;%Y\n' 2>/dev/null";
            }
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
                if ($this->appUtil->isHostRunningOnFreeBSD()) {
                    // BSD stat format: %N|%z|%Lp|%HT|%m
                    $parts = explode('|', $line);

                    // check if we have all the expected parts
                    if (count($parts) < 5) {
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

                    [$filePath, $size, $permissions, $typeDescription, $creationTime] = $parts;
                    $name = basename($filePath);
                    $realPath = $filePath;
                    $type = str_contains($typeDescription, 'Directory') ? 'd' : 'f';
                } else {
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
                }

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
                    'creationTime' => date('Y-m-d H:i:s', (int)$creationTime)
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

            // handle pagination if page and limit are provided
            if ($page !== null && $limit !== null) {
                $offset = ($page - 1) * $limit;
                return array_slice($files, $offset, $limit);
            }
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
     * Get files count in path
     *
     * @param string $path
     *
     * @return int
     */
    public function getFilesCount(string $path): int
    {
        $sudoPath = $this->appUtil->getSudoPath();

        // set default path if is empty
        if (empty($path)) {
            $path = '/';
        }

        try {
            // skip system directories that might cause permission issues
            if (in_array($path, ['/proc', '/sys', '/dev', '/run'])) {
                return 0;
            }

            // execute find command with exclusions for system directories and count lines
            $depth = '-mindepth 1 -maxdepth 1';
            $excludes = '-not -path "/proc/*" -not -path "/sys/*" -not -path "/dev/*" -not -path "/run/*"';
            $command = "$sudoPath find " . escapeshellarg($path) . " $depth $excludes 2>/dev/null | wc -l";
            $output = shell_exec($command);

            // check if output is empty or not set
            if ($output === null || $output === false) {
                return 0;
            }

            return (int) trim($output);
        } catch (Exception $e) {
            $this->errorManager->handleError(
                message: 'error counting files: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
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
        $sudoPath = $this->appUtil->getSudoPath();

        // check file exists
        if (!$this->checkIfFileExist($path)) {
            return false;
        }

        // check if path is directory
        if ($this->isPathDirectory($path) || $this->checkIfFileIsSymlink($path)) {
            return false;
        }

        // check if file is a log file (by extension or name)
        $fileName = $this->getBasename($path);
        if (str_ends_with($fileName, '.log') || str_contains($fileName, 'log') || str_contains($fileName, 'exception') || str_contains($path, '/log/')) {
            return false;
        }

        // check if file is media file
        if ($this->detectMediaType($path) !== 'non-mediafile') {
            return false;
        }

        // check if file has executable permissions
        $perms = $this->getFilePermissions($path);
        if ($perms !== null && ($perms & 0o111)) {
            return true;
        }

        // get file info
        $fileInfo = exec($sudoPath . 'file ' . escapeshellarg($path));

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
        $sudoPath = $this->appUtil->getSudoPath();

        // check if file exists
        if (!$this->checkIfFileExist($path)) {
            return 'non-mediafile';
        }

        // check if path is a directory or symbolic link
        if ($this->isPathDirectory($path) || $this->checkIfFileIsSymlink($path)) {
            return 'non-mediafile';
        }

        // supported extensions mapping
        $extensionMap = [
            // images
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'bmp' => 'image/bmp',
            'tiff' => 'image/tiff',
            'svg' => 'image/svg+xml',

            // videos
            'mp4' => 'video/mp4',
            'mkv' => 'video/x-matroska',
            'mov' => 'video/quicktime',
            'avi' => 'video/x-msvideo',
            'webm' => 'video/webm',
            'flv' => 'video/x-flv',

            // audio
            'mp3' => 'audio/mpeg',
            'wav' => 'audio/wav',
            'flac' => 'audio/flac',
            'ogg' => 'audio/ogg',
            'm4a' => 'audio/mp4',
            'aac' => 'audio/aac'
        ];

        // get extension
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        // if extension exists and we know it
        if ($ext !== '' && isset($extensionMap[$ext])) {
            return $extensionMap[$ext];
        }

        // --- FALLBACK: file --mime-type ---
        $cmd = "$sudoPath file --mime-type -b " . escapeshellarg($path);
        $mimeType = shell_exec($cmd);
        if (!$mimeType) {
            $this->errorManager->handleError(
                message: 'Error: Unable to detect MIME type for ' . $path,
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
        $mimeType = trim($mimeType);

        // determine if file is an image, video, or audio based on MIME prefix
        if (str_starts_with($mimeType, 'image/') || str_starts_with($mimeType, 'video/') || str_starts_with($mimeType, 'audio/')) {
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
        $sudoPath = $this->appUtil->getSudoPath();

        try {
            // check if path is directory
            if ($this->isPathDirectory($path) || $this->checkIfFileIsSymlink($path)) {
                $this->errorManager->handleError(
                    message: 'error opening file: ' . $path . ' is a directory or a link',
                    code: Response::HTTP_BAD_REQUEST
                );
            }

            // get file size
            $statFormat = $this->appUtil->isHostRunningOnFreeBSD() ? '-f %z' : '-c %s';
            $cmd = $sudoPath . '/usr/bin/stat ' . $statFormat . ' ' . escapeshellarg($path);
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
            $totalLinesOutput = shell_exec($sudoPath . 'wc -l ' . escapeshellarg($path));

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
                    $command = $sudoPath . 'head -n ' . $maxLines . ' ' . escapeshellarg($path);
                } elseif ($startLine > $totalLines - $maxLines) {
                    // if near the end, use tail for better performance
                    $linesToTake = $totalLines - $startLine + 1;
                    $command = $sudoPath . 'tail -n ' . $linesToTake . ' ' . escapeshellarg($path);
                } else {
                    // use sed to extract lines from the middle
                    $command = $sudoPath . 'sed -n \'' . $startLine . ',' . $endLine . 'p\' ' . escapeshellarg($path);
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
                $fileContent = shell_exec($sudoPath . 'cat ' . escapeshellarg($path));

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
        $sudoPath = $this->appUtil->getSudoPath();

        try {
            // check if path is directory
            if ($this->isPathDirectory($path) || $this->checkIfFileIsSymlink($path)) {
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
            $fileInfo = exec($sudoPath . 'file ' . escapeshellarg($path));
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

            // use cat to preserve line endings instead of tee FreeBSD uses sh instead of bash
            $shell = $this->appUtil->isHostRunningOnFreeBSD() ? 'sh' : 'bash';
            $command = $sudoPath . 'cat ' . escapeshellarg($tempFile) . ' > ' . escapeshellarg($path);
            $output = shell_exec($sudoPath . $shell . ' -c ' . escapeshellarg($command) . ' 2>&1');

            // check if command was successful
            if ($output !== null && !empty($output)) {
                throw new Exception('Failed to save file: ' . $output);
            }

            // restore original permissions if it was executable
            if ($originalPerms !== null && ($originalPerms & 0111)) {
                $chmodCommand = $sudoPath . 'chmod ' . sprintf('%o', $originalPerms & 0777) . ' ' . escapeshellarg($path);
                shell_exec($chmodCommand);
            } elseif ($isShellScript) {
                // make shell scripts executable
                $chmodCommand = $sudoPath . 'chmod +x ' . escapeshellarg($path);
                shell_exec($chmodCommand);
            }

            // restore original owner and group
            if ($fileOwner !== null && $fileGroup !== null) {
                $chownCommand = $sudoPath . 'chown ' . $fileOwner . ':' . $fileGroup . ' ' . escapeshellarg($path);
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
        $sudoPath = $this->appUtil->getSudoPath();

        // check if file exists
        if (!file_exists($path)) {
            return false;
        }

        // check if path is directory or link
        if ($this->isPathDirectory($path) || $this->checkIfFileIsSymlink($path)) {
            return false;
        }

        // special case for shell scripts - allow editing
        $fileInfo = exec($sudoPath . 'file ' . escapeshellarg($path));
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
        $mimeType = shell_exec("$sudoPath file --mime-type -b " . escapeshellarg($path));

        // check if MIME type is detected
        if (!$mimeType) {
            return false;
        }

        // trim output
        $mimeType = trim($mimeType);

        // check if file is a media file
        if (str_starts_with($mimeType, 'image/') || str_starts_with($mimeType, 'video/') || str_starts_with($mimeType, 'audio/')) {
            return false;
        }

        // check if file is a binary file
        if (str_starts_with($mimeType, 'application/') && !str_contains($mimeType, 'text') && !str_contains($mimeType, 'json') && !str_contains($mimeType, 'xml')) {
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
        $sudoPath = $this->appUtil->getSudoPath();

        try {
            // check if path exists
            if (!$this->checkIfFileExist($path)) {
                $this->errorManager->handleError(
                    message: 'error deleting file: ' . $path . ' does not exist',
                    code: Response::HTTP_BAD_REQUEST
                );
            }

            // check if path is a directory
            if ($this->isPathDirectory($path)) {
                // always use rm -rf for directories (empty or not)
                $command = $sudoPath . 'rm -rf ' . escapeshellarg($path);
            } else {
                // delete file using sudo
                $command = $sudoPath . 'rm ' . escapeshellarg($path);
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
        $sudoPath = $this->appUtil->getSudoPath();

        try {
            // check if old path exists
            if (!$this->checkIfFileExist($oldPath)) {
                $this->errorManager->handleError(
                    message: 'error renaming file: ' . $oldPath . ' does not exist',
                    code: Response::HTTP_BAD_REQUEST
                );
            }

            // check if new path already exists
            if ($this->checkIfFileExist($newPath)) {
                throw new Exception('Destination already exists: ' . $newPath);
            }

            // rename file or directory using sudo
            $command = $sudoPath . 'mv ' . escapeshellarg($oldPath) . ' ' . escapeshellarg($newPath);
            $output = shell_exec($command);

            // check if command was successful
            if ($output !== null && !empty($output)) {
                throw new Exception('Failed to rename file or directory: ' . $output);
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
     * @param int|null $mode Optional chmod mode (e.g., 0755). If null, default system umask is used
     *
     * @return bool True if the directory was created successfully, false otherwise
     */
    public function createDirectory(string $path, ?int $mode = null): bool
    {
        $sudoPath = $this->appUtil->getSudoPath();

        try {
            // check if path already exists
            if (file_exists($path)) {
                $this->errorManager->handleError(
                    message: 'error creating directory: ' . $path . ' already exists',
                    code: Response::HTTP_BAD_REQUEST
                );
            }

            // build sudo command (mkdir -p [-m <mode>] <path>)
            $cmd = $sudoPath . 'mkdir -p ';

            if ($mode !== null) {
                // ensure mode is e.g. 0755, 0770 … convert to octal string
                $cmd .= '-m ' . sprintf('%04o', $mode) . ' ';
            }

            $cmd .= escapeshellarg($path);
            $output = shell_exec($cmd . ' 2>&1');

            // check if command wrote something = error
            if (!empty($output)) {
                throw new Exception('Failed to create directory: ' . $output);
            }

            return true;
        } catch (Exception $e) {
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
        $sudoPath = $this->appUtil->getSudoPath();

        try {
            // check if path is directory
            if ($this->isPathDirectory($path) || is_link($path)) {
                $this->errorManager->handleError(
                    message: 'error opening file: ' . $path . ' is a directory or a link',
                    code: Response::HTTP_BAD_REQUEST
                );
            }

            // get file content using cat
            $fileContent = shell_exec($sudoPath . 'cat ' . escapeshellarg($path));

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
        $sudoPath = $this->appUtil->getSudoPath();

        try {
            // check if path exists and is a directory
            if (!$this->checkIfFileExist($path) || !$this->isPathDirectory($path)) {
                return 0;
            }

            // use du command to get directory size (BSD du has no -b flag, use -sk)
            if ($this->appUtil->isHostRunningOnFreeBSD()) {
                $command = $sudoPath . 'du -sk ' . escapeshellarg($path) . ' 2>&1';
            } else {
                $command = $sudoPath . 'du -sb ' . escapeshellarg($path) . ' 2>&1';
            }
            $output = shell_exec($command);

            // check if output is empty or not set
            if ($output === null || $output === false) {
                return 0;
            }

            // parse output to get the size
            if (preg_match('/^(\d+)\s+/', $output, $matches)) {
                // BSD du reports size in 1024-byte blocks
                $size = (int)$matches[1];
                return $this->appUtil->isHostRunningOnFreeBSD() ? $size * 1024 : $size;
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
        $exponent = (int) floor(log($bytes, $base));
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
        $sudoPath = $this->appUtil->getSudoPath();

        try {
            // validate source path
            if (!$this->checkIfFileExist($sourcePath)) {
                $this->errorManager->handleError(
                    message: 'Source path does not exist: ' . $sourcePath,
                    code: Response::HTTP_BAD_REQUEST
                );
            }

            // validate destination directory
            if (
                !$this->checkIfFileExist($destinationPath) ||
                !$this->isPathDirectory($destinationPath)
            ) {
                $this->errorManager->handleError(
                    message: 'Destination path is not a valid directory: ' . $destinationPath,
                    code: Response::HTTP_BAD_REQUEST
                );
            }

            // basename of the source
            $basename = $this->getBasename($sourcePath);

            // compute final target path (dir + basename)
            $newPath = rtrim($destinationPath, '/') . '/' . $basename;

            // prevent overwriting existing file/directory
            if ($this->checkIfFileExist($newPath)) {
                throw new Exception('Destination already exists: ' . $newPath);
            }

            // prevent moving a dir into its own subdirectory
            if ($this->isPathDirectory($sourcePath)) {
                $normalizedSource = rtrim($sourcePath, '/');
                $normalizedDest   = rtrim($destinationPath, '/');

                if (str_starts_with($normalizedDest . '/', $normalizedSource . '/')) {
                    throw new Exception('Cannot move directory into its own subdirectory');
                }
            }

            // build sudo command
            $cmd = sprintf($sudoPath . 'mv %s %s 2>&1', escapeshellarg($sourcePath), escapeshellarg($newPath));
            $output = (string) shell_exec($cmd);

            // any output from sudo mv means error
            if (trim($output) !== '') {
                throw new Exception('mv error: ' . $output);
            }

            return true;
        } catch (Exception $e) {
            $this->errorManager->logError(
                message: 'Error moving file/directory: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );

            return false;
        }
    }

    /**
     * Check if file is a shell script
     *
     * @param string $path The path to the file
     *
     * @return bool True if the file is a shell script, false otherwise
     */
    public function isShellScript(string $path): bool
    {
        $sudoPath = $this->appUtil->getSudoPath();

        // check if path is directory
        if ($this->isPathDirectory($path) || $this->checkIfFileIsSymlink($path)) {
            return false;
        }

        // get file info
        $fileInfo = exec($sudoPath . 'file ' . escapeshellarg($path));

        // check if file info is set
        if (!$fileInfo) {
            $this->errorManager->handleError(
                message: 'error get file info: ' . $path . ' file info detection failed',
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        // check if file is a shell script
        if (strpos($fileInfo, 'shell script') !== false || str_ends_with($path, '.sh') || str_ends_with($path, '.bash')) {
            return true;
        }

        return false;
    }

    /**
     * Get directory name of a file
     *
     * @param string $path The path to the file
     *
     * @return string The directory name
     */
    public function getDirname(string $path): string
    {
        $sudoPath = $this->appUtil->getSudoPath();
        $out = shell_exec($sudoPath . 'dirname ' . escapeshellarg($path));

        // check if command returned null
        if ($out === null || $out === false) {
            return '';
        }

        return trim($out);
    }

    /**
     * Make a shell script executable
     *
     * @param string $filePath The path to the file
     *
     * @return bool True if the file was made executable, false otherwise
     */
    public function makeScriptExecutable(string $filePath): bool
    {
        $sudoPath = $this->appUtil->getSudoPath();
        $chmodCommand = $sudoPath . 'chmod +x ' . escapeshellarg($filePath);
        $out = shell_exec($chmodCommand);

        // check if command returned null
        if ($out === null || $out === false) {
            return false;
        }

        return true;
    }

    /**
     * Get file modification time
     *
     * @param string $path The path to the file
     *
     * @return int The file modification time
     */
    public function getMtime(string $path): int
    {
        $sudoPath = $this->appUtil->getSudoPath();
        $statFormat = $this->appUtil->isHostRunningOnFreeBSD() ? '-f %m' : '-c %Y';
        $mtime = shell_exec($sudoPath . 'stat ' . $statFormat . ' ' . escapeshellarg($path));
        return (int) $mtime;
    }

    /**
     * Get file name
     *
     * @param string $path The path to the file
     *
     * @return string The file name
     */
    public function getBasename(string $path): string
    {
        $sudoPath = $this->appUtil->getSudoPath();
        $out = shell_exec($sudoPath . 'basename ' . escapeshellarg($path));

        // check if command returned null
        if ($out === null || $out === false) {
            return '';
        }

        return trim($out);
    }

    /**
     * Get file size
     *
     * @param string $path The path to the file
     *
     * @return int The file size
     */
    public function getFileSize(string $path): int
    {
        $sudoPath = $this->appUtil->getSudoPath();
        $statFormat = $this->appUtil->isHostRunningOnFreeBSD() ? '-f %z' : '-c %s';
        $fileSize = shell_exec($sudoPath . 'stat ' . $statFormat . ' ' . escapeshellarg($path));
        return (int) $fileSize;
    }
}
