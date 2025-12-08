<?php

namespace App\Util;

use Exception;
use App\Manager\ErrorManager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * Class FileUploadUtil
 *
 * Util for file upload functionality
 *
 * @package App\Util
 */
class FileUploadUtil
{
    private ErrorManager $errorManager;
    private FileSystemUtil $fileSystemUtil;

    public function __construct(ErrorManager $errorManager, FileSystemUtil $fileSystemUtil)
    {
        $this->errorManager = $errorManager;
        $this->fileSystemUtil = $fileSystemUtil;
    }

    /**
     * Combine uploaded chunks into final file
     *
     * @param string $tempDir The temporary directory containing chunks
     * @param string $targetPath The target file path
     * @param int $totalChunks The total number of chunks
     *
     * @return bool True if chunks were combined successfully, false otherwise
     */
    public function combineChunks(string $tempDir, string $targetPath, int $totalChunks): bool
    {
        try {
            // create temporary file for combining chunks
            $tempFile = tempnam(sys_get_temp_dir(), 'admin_suite_combine_');
            if ($tempFile === false) {
                return false;
            }

            // open temp file for writing
            $outputHandle = fopen($tempFile, 'wb');
            if ($outputHandle === false) {
                unlink($tempFile);
                return false;
            }

            // combine chunks in order
            for ($i = 0; $i < $totalChunks; $i++) {
                $chunkPath = $tempDir . '/chunk_' . $i;
                if (!$this->fileSystemUtil->checkIfFileExist($chunkPath)) {
                    fclose($outputHandle);
                    unlink($tempFile);
                    return false;
                }

                $chunkHandle = fopen($chunkPath, 'rb');
                if ($chunkHandle === false) {
                    fclose($outputHandle);
                    unlink($tempFile);
                    return false;
                }

                // copy chunk data to output file
                while (!feof($chunkHandle)) {
                    $data = fread($chunkHandle, 8192);
                    if ($data === false) {
                        fclose($chunkHandle);
                        fclose($outputHandle);
                        unlink($tempFile);
                        return false;
                    }
                    fwrite($outputHandle, $data);
                }
                fclose($chunkHandle);
            }

            fclose($outputHandle);

            // move combined file to target location using sudo
            $command = 'sudo mv ' . escapeshellarg($tempFile) . ' ' . escapeshellarg($targetPath);
            $output = shell_exec($command);

            // check if move was successful
            if ($output !== null && !empty($output)) {
                unlink($tempFile);
                return false;
            }

            return $this->fileSystemUtil->checkIfFileExist($targetPath);
        } catch (Exception) {
            return false;
        }
    }

    /**
     * Save uploaded file chunk
     *
     * @param UploadedFile $file The uploaded file
     * @param string $targetDir The target directory
     * @param string $targetFilename The target filename
     * @param bool $useSudo Use sudo for file operations (default: false)
     *
     * @return bool True if the chunk was saved successfully, false otherwise
     */
    public function saveUploadedChunk(UploadedFile $file, string $targetDir, string $targetFilename, bool $useSudo = false): bool
    {
        try {
            // ensure target dir exists
            if (!is_dir($targetDir)) {
                if ($useSudo) {
                    shell_exec('sudo mkdir -p ' . escapeshellarg($targetDir));
                    shell_exec('sudo chown www-data:www-data ' . escapeshellarg($targetDir));
                } else {
                    mkdir($targetDir, 0755, true);
                }
            }

            // php tmp path
            $tmpPath = $file->getPathname();

            // final path
            $finalPath = rtrim($targetDir, '/') . '/' . $targetFilename;

            if ($useSudo) {
                // sudo mv tmpfile finalfile
                $cmd = 'sudo mv ' . escapeshellarg($tmpPath) . ' ' . escapeshellarg($finalPath) . ' 2>&1';
                $out = (string) shell_exec($cmd);

                if (trim($out) !== '') {
                    throw new Exception("sudo mv error: " . $out);
                }

                // set readable perms
                shell_exec('sudo chmod 0644 ' . escapeshellarg($finalPath));
            } else {
                if (!@rename($tmpPath, $finalPath)) {
                    throw new Exception("rename() failed");
                }
            }

            return true;
        } catch (Exception $e) {
            $this->errorManager->logError(
                message: 'saveUploadedChunk error: ' . $e->getMessage(),
                code: 500
            );
            return false;
        }
    }

    /**
     * List uploaded chunks
     *
     * @param string $tempDir The temporary directory containing chunks
     * @param string $prefix The prefix for chunk filenames (default: 'chunk_')
     * @param bool $useSudo Use sudo for file operations (default: false)
     *
     * @return array<string> The list of full paths to uploaded chunks
     */
    public function listChunks(string $tempDir, string $prefix = 'chunk_', bool $useSudo = false): array
    {
        try {
            if (!$useSudo) {
                if (!is_dir($tempDir)) {
                    throw new Exception("Directory does not exist: $tempDir");
                }

                $files = scandir($tempDir);
                if ($files === false) {
                    throw new Exception("Failed to read directory: $tempDir");
                }

                // chunk filter
                $chunks = array_values(
                    array_filter($files, fn($f) => str_starts_with($f, $prefix))
                );

                // sort chunks by index
                natsort($chunks);

                // return full paths
                return array_map(fn($f) => $tempDir . '/' . $f, $chunks);
            }

            // find chunks using sudo
            $cmd = 'sudo find ' . escapeshellarg($tempDir) . ' -maxdepth 1 -type f -name ' . escapeshellarg($prefix . '*') . ' 2>&1';
            $output = shell_exec($cmd);
            if ($output === null || $output === false) {
                throw new Exception("sudo find returned null");
            }

            // split output to lines
            $lines = array_filter(explode("\n", trim($output)));
            natsort($lines);

            // return full paths
            return array_values($lines);
        } catch (Exception $e) {
            $this->errorManager->logError(
                message: 'listChunks error: ' . $e->getMessage(),
                code: 500
            );
            return [];
        }
    }

    /**
     * Clean up temporary directory and its contents
     *
     * @param string $tempDir The temporary directory to clean up
     *
     * @return void
     */
    public function cleanupTempDirectory(string $tempDir): void
    {
        try {
            // use sudo rm -rf to remove all files and the directory itself
            $cmd = 'sudo rm -rf ' . escapeshellarg($tempDir) . ' 2>&1';
            $output = (string) shell_exec($cmd);

            // if rm returned error output, log it
            if (trim($output) !== '') {
                throw new Exception('sudo rm -rf failed: ' . $output);
            }
        } catch (Exception $e) {
            $this->errorManager->handleError(
                message: 'error to cleanup temp directory: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Stream file range for media files with Range support (with dd command)
     *
     * @param string $path The file path
     * @param int $start Start byte position
     * @param int $length Number of bytes to stream
     *
     * @return void
     */
    public function streamFileRange(string $path, int $start, int $length): void
    {
        try {
            // prevent any php output buffering
            if (ob_get_level()) {
                ob_end_clean();
            }

            // build dd command
            $cmd = sprintf('sudo dd if=%s bs=1 skip=%d count=%d 2>/dev/null', escapeshellarg($path), $start, $length);

            // open a process handle
            $process = popen($cmd, 'rb');
            if (!$process) {
                return;
            }

            // stream out in chunks (64KB default)
            $buffer = 65536;

            while (!feof($process)) {
                $chunk = fread($process, $buffer);
                if ($chunk === false || $chunk === '') {
                    break;
                }
                echo $chunk;
                flush();
                if (connection_status() !== CONNECTION_NORMAL) {
                    break;
                }
            }

            pclose($process);
        } catch (Exception $e) {
            $this->errorManager->handleError(
                message: 'error to stream file range: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
