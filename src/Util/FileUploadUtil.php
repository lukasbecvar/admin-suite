<?php

namespace App\Util;

use Exception;
use App\Manager\ErrorManager;
use Symfony\Component\HttpFoundation\Response;

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

    public function __construct(ErrorManager $errorManager)
    {
        $this->errorManager = $errorManager;
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
                if (!file_exists($chunkPath)) {
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

            return file_exists($targetPath);
        } catch (Exception) {
            return false;
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
            if (is_dir($tempDir)) {
                // remove all files in temp directory
                $files = glob($tempDir . '/*');

                // check if files are iterable
                if (!is_iterable($files)) {
                    return;
                }

                foreach ($files as $file) {
                    if (is_file($file)) {
                        unlink($file);
                    }
                }
                // remove directory
                rmdir($tempDir);
            }
        } catch (Exception $e) {
            $this->errorManager->handleError(
                message: 'error to cleanup temp directory: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Stream file range for media files with Range support
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
            // use PHP file handling for better performance than dd command
            $handle = fopen($path, 'rb');
            if ($handle === false) {
                return;
            }

            // seek to start position
            if ($start > 0) {
                fseek($handle, $start);
            }

            // disable output buffering for immediate streaming
            if (ob_get_level()) {
                ob_end_clean();
            }

            // stream data in larger chunks for better performance
            $chunkSize = 65536; // 64KB chunks - optimal for video streaming
            $bytesStreamed = 0;

            while (!feof($handle) && $bytesStreamed < $length) {
                $remainingBytes = $length - $bytesStreamed;
                $currentChunkSize = max(1, min($chunkSize, $remainingBytes));

                $chunk = fread($handle, $currentChunkSize);
                if ($chunk === false || $chunk === '') {
                    break;
                }

                echo $chunk;
                $bytesStreamed += strlen($chunk);

                // flush output immediately for streaming
                flush();

                // prevent timeout for large files
                if (connection_status() !== CONNECTION_NORMAL) {
                    break;
                }
            }

            fclose($handle);
        } catch (Exception $e) {
            $this->errorManager->handleError(
                message: 'error to stream file range: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
