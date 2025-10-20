<?php

namespace App\Manager;

use App\Util\AppUtil;
use RuntimeException;
use App\Util\SessionUtil;
use Symfony\Component\Process\Process;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class TerminalJobManager
 *
 * Terminal command job manager
 *
 * @package App\Manager
 */
class TerminalJobManager
{
    private const DEFAULT_MAX_CHUNK_SIZE = 65536;
    private const INPUT_FILE_NAME = 'input.queue';

    private AppUtil $appUtil;
    private Filesystem $filesystem;
    private SessionUtil $sessionUtil;

    public function __construct(AppUtil $appUtil, Filesystem $filesystem, SessionUtil $sessionUtil)
    {
        $this->appUtil = $appUtil;
        $this->filesystem = $filesystem;
        $this->sessionUtil = $sessionUtil;

        // create jobs directory if it does not exist
        if (!$this->filesystem->exists($this->appUtil->getAppRootDir() . '/var/terminal-jobs')) {
            $this->filesystem->mkdir($this->appUtil->getAppRootDir() . '/var/terminal-jobs', 0700);
        }
    }

    /**
     * Start background command execution
     *
     * @param string $command The command to execute
     * @param string $sudoUser The user that should execute the command
     * @param string $workingDirectory Working directory for the command
     *
     * @return array{
     *     jobId: string,
     *     pid: int,
     *     startedAt: int
     * }
     */
    public function startJob(string $command, string $sudoUser, string $workingDirectory): array
    {
        $jobId = $this->appUtil->generateKey(16);
        $sessionId = $this->sanitizeIdentifier($this->sessionUtil->getSessionId());
        $jobDirectory = $this->getJobDirectory($sessionId, $jobId);

        $logFile = $jobDirectory . '/output.log';
        $exitCodeFile = $jobDirectory . '/exit-code';
        $pidFile = $jobDirectory . '/pid';
        $metaFile = $jobDirectory . '/meta.json';
        $inputFile = $this->getInputFilePath($jobDirectory);

        // create log file upfront so permissions remain accessible by PHP user
        $this->filesystem->touch($logFile);
        $this->filesystem->touch($exitCodeFile);
        $this->filesystem->dumpFile($inputFile, '');
        $maxRuntime = $this->getConfiguredMaxRuntime();

        $startedAt = time();
        $metaData = json_encode([
            'command' => $command,
            'user' => $sudoUser,
            'working_directory' => $workingDirectory,
            'started_at' => $startedAt,
            'execution_mode' => 'interactive-runner',
            'max_runtime' => $maxRuntime,
        ], JSON_PRETTY_PRINT);

        // check if metadata encoding was successful
        if ($metaData === false) {
            throw new RuntimeException('Failed to encode terminal job metadata.');
        }

        // write metadata to file
        $this->filesystem->dumpFile($metaFile, $metaData);

        // run command process
        $runnerCommand = $this->buildRunnerCommand($jobDirectory);
        $process = Process::fromShellCommandline($runnerCommand);
        $process->run();

        // check if command process was successful
        if (!$process->isSuccessful()) {
            $this->filesystem->remove($jobDirectory);
            $errorMessage = trim($process->getErrorOutput());

            // check if error message is empty
            if ($errorMessage === '') {
                $errorMessage = trim($process->getOutput());
            }

            // throw exception
            throw new RuntimeException(sprintf('Failed to start terminal job: %s', $errorMessage !== '' ? $errorMessage : 'unable to spawn runner'));
        }

        // wait for runner process to report PID
        $pid = $this->waitForPid($pidFile, 20, 100000);
        if ($pid === null) {
            $this->filesystem->remove($jobDirectory);
            throw new RuntimeException('Failed to start terminal job: runner did not report PID.');
        }

        return [
            'pid' => $pid,
            'jobId' => $jobId,
            'startedAt' => $startedAt,
            'mode' => 'interactive-runner'
        ];
    }

    /**
     * Fetch incremental output for a job
     *
     * @param string $jobId The job identifier
     * @param int $offset Current read offset
     * @param int|null $limit Maximum number of bytes to read
     *
     * @return array{
     *     chunk: string,
     *     offset: int,
     *     isRunning: bool,
     *     exitCode: int|null,
     *     startedAt: int|null,
     *     executionMode: string|null
     * }
     */
    public function getOutput(string $jobId, int $offset = 0, ?int $limit = null): array
    {
        $sessionId = $this->sanitizeIdentifier($this->sessionUtil->getSessionId());
        $jobDirectory = $this->getJobDirectory($sessionId, $jobId, false);
        $logFile = $jobDirectory . '/output.log';
        $exitCodeFile = $jobDirectory . '/exit-code';
        $pid = $this->getPid($jobDirectory);
        $meta = $this->readMeta($jobDirectory);

        // check if job output file exists
        if (!$this->filesystem->exists($logFile)) {
            throw new RuntimeException('Job output file not found.');
        }

        // open job output file
        $handle = fopen($logFile, 'rb');
        if ($handle === false) {
            throw new RuntimeException('Unable to read job output file.');
        }

        // get file size
        $fileSize = filesize($logFile);
        if ($fileSize === false) {
            $fileSize = 0;
        }

        if ($offset > $fileSize) {
            $offset = $fileSize;
        }

        fseek($handle, $offset);

        $bytesToRead = $limit ?? self::DEFAULT_MAX_CHUNK_SIZE;
        $chunk = ($bytesToRead === 0) ? '' : stream_get_contents($handle, $bytesToRead);

        if ($chunk === false) {
            $chunk = '';
        }

        $offset += strlen($chunk);
        fclose($handle);

        $isRunning = $this->isProcessRunning($pid);
        $exitCode = null;

        // check if job exit code file exists
        if (!$isRunning && $this->filesystem->exists($exitCodeFile)) {
            $exitCodeRaw = trim((string)@file_get_contents($exitCodeFile));

            if ($exitCodeRaw !== '') {
                $exitCode = (int)$exitCodeRaw;
            }
        }

        return [
            'chunk' => $chunk,
            'offset' => $offset,
            'exitCode' => $exitCode,
            'isRunning' => $isRunning,
            'startedAt' => $meta['started_at'] ?? null,
            'executionMode' => $meta['execution_mode'] ?? null
        ];
    }

    /**
     * Append interactive input to running job
     *
     * @param string $jobId The job identifier
     * @param string $input The input value
     *
     * @return void
     */
    public function appendInput(string $jobId, string $input): void
    {
        $sessionId = $this->sanitizeIdentifier($this->sessionUtil->getSessionId());
        $jobDirectory = $this->getJobDirectory($sessionId, $jobId, false);
        $inputFile = $this->getInputFilePath($jobDirectory);

        // check if input channel is available
        if (!$this->filesystem->exists($inputFile)) {
            throw new RuntimeException('Terminal job input channel is not available.');
        }

        // normalize input value
        $normalizedInput = str_replace(["\r\n", "\r"], "\n", $input);
        if (!str_ends_with($normalizedInput, "\n")) {
            $normalizedInput .= "\n";
        }

        // write input to file
        $bytesWritten = @file_put_contents($inputFile, $normalizedInput, FILE_APPEND | LOCK_EX);
        if ($bytesWritten === false) {
            throw new RuntimeException('Unable to send input to the terminal job.');
        }
    }

    /**
     * Stop running job by sending SIGTERM
     *
     * @param string $jobId The job identifier
     *
     * @return void
     */
    public function stopJob(string $jobId): void
    {
        try {
            $sessionId = $this->sanitizeIdentifier($this->sessionUtil->getSessionId());
        } catch (RuntimeException) {
            return;
        }

        try {
            $jobDirectory = $this->getJobDirectory($sessionId, $jobId, false);
        } catch (RuntimeException) {
            return;
        }

        $this->requestStop($jobDirectory);
        $pid = $this->getPid($jobDirectory);

        if ($pid === null) {
            return;
        }

        if (!$this->isProcessRunning($pid)) {
            return;
        }

        if (function_exists('posix_kill')) {
            @posix_kill($pid, SIGTERM);
        } else {
            $killProcess = Process::fromShellCommandline(sprintf('kill -TERM %d', $pid));
            $killProcess->run();
        }
    }

    /**
     * Generate job directory path
     *
     * @param string $sessionId The session identifier
     * @param string $jobId The job identifier
     * @param bool $autoCreate Whether to create the directory if it does not exist
     *
     * @return string The job directory path
     */
    private function getJobDirectory(string $sessionId, string $jobId, bool $autoCreate = true): string
    {
        $safeJobId = $this->sanitizeIdentifier($jobId);
        $directory = sprintf('%s/%s/%s', $this->appUtil->getAppRootDir() . '/var/terminal-jobs', $sessionId, $safeJobId);

        // check if directory exists
        if (!$this->filesystem->exists($directory)) {
            if (!$autoCreate) {
                throw new RuntimeException('Terminal job not found.');
            }

            $this->filesystem->mkdir($directory, 0700);
        }

        return $directory;
    }

    /**
     * Get process ID for job if available
     *
     * @param string $jobDirectory The job directory path
     *
     * @return int|null The process ID or null if not available
     */
    private function getPid(string $jobDirectory): ?int
    {
        $pidFile = $jobDirectory . '/pid';

        // check if PID file exists
        if (!$this->filesystem->exists($pidFile)) {
            return null;
        }

        // read PID from file
        $pid = trim((string)@file_get_contents($pidFile));
        if ($pid === '' || !ctype_digit($pid)) {
            return null;
        }

        return (int) $pid;
    }

    /**
     * Check if process with provided PID is still running
     *
     * @param int|null $pid The process ID or null if not available
     *
     * @return bool True if the process is running, false otherwise
     */
    private function isProcessRunning(?int $pid): bool
    {
        if ($pid === null || $pid <= 0) {
            return false;
        }

        return file_exists(sprintf('/proc/%d', $pid));
    }

    /**
     * Sanitize session/job identifiers to file-system-safe values
     *
     * @param string $value The identifier value
     *
     * @return string The sanitized identifier value
     */
    private function sanitizeIdentifier(string $value): string
    {
        $sanitized = preg_replace('/[^A-Za-z0-9_\-]/', '', $value);

        // check if sanitized value is empty
        if ($sanitized === null || $sanitized === '') {
            throw new RuntimeException('Unable to build terminal job identifier.');
        }

        return $sanitized;
    }

    /**
     * Read job metadata file
     *
     * @param string $jobDirectory The job directory path
     *
     * @return array<mixed> The metadata
     */
    private function readMeta(string $jobDirectory): array
    {
        $metaFile = $jobDirectory . '/meta.json';

        // check if metadata file exists
        if (!$this->filesystem->exists($metaFile)) {
            return [];
        }

        // read metadata file
        $content = (string)@file_get_contents($metaFile);
        if ($content === '') {
            return [];
        }

        /** @var array<mixed>|null $decoded */
        $decoded = json_decode($content, true);

        // check if metadata is valid
        if (!is_array($decoded)) {
            return [];
        }

        return $decoded;
    }

    /**
     * Build command used to spawn the job runner process
     *
     * @param string $jobDirectory The job directory path
     *
     * @return string The command
     */
    private function buildRunnerCommand(string $jobDirectory): string
    {
        $phpBinary = $this->resolvePhpBinary();
        $runnerScript = $this->appUtil->getAppRootDir() . '/bin/terminal-job-runner.php';

        $baseCommand = sprintf(
            'cd %s && %s %s --job=%s',
            escapeshellarg($this->appUtil->getAppRootDir()),
            escapeshellarg($phpBinary),
            escapeshellarg($runnerScript),
            escapeshellarg($jobDirectory)
        );

        $wrapped = sprintf('bash -lc %s', escapeshellarg($baseCommand));

        return sprintf('%s > /dev/null 2>&1 & echo $!', $wrapped);
    }

    /**
     * Get configured maximum runtime for a job
     *
     * @return int|null The maximum runtime in seconds or null if not set
     */
    private function getConfiguredMaxRuntime(): ?int
    {
        $raw = $_ENV['TERMINAL_JOB_MAX_RUNTIME']
            ?? $_SERVER['TERMINAL_JOB_MAX_RUNTIME']
            ?? getenv('TERMINAL_JOB_MAX_RUNTIME');

        if ($raw === false) {
            return null;
        }

        $value = trim((string) $raw);
        if ($value === '') {
            return null;
        }

        if (!is_numeric($value)) {
            return null;
        }

        $seconds = (int) $value;
        if ($seconds <= 0) {
            return null;
        }

        return $seconds;
    }

    /**
     * Wait until runner process persists its PID file
     *
     * @param string $pidFile The PID file path
     * @param int $attempts The number of attempts
     * @param int $sleepMicroseconds The sleep time in microseconds
     *
     * @return int|null The PID or null if not available
     */
    private function waitForPid(string $pidFile, int $attempts, int $sleepMicroseconds): ?int
    {
        for ($i = 0; $i < $attempts; $i++) {
            clearstatcache(true, $pidFile);

            // check if PID file exists
            if ($this->filesystem->exists($pidFile)) {
                $pidRaw = trim((string) @file_get_contents($pidFile));
                if ($pidRaw !== '' && ctype_digit($pidRaw)) {
                    return (int) $pidRaw;
                }
            }

            usleep($sleepMicroseconds);
        }

        return null;
    }

    /**
     * Locate input queue file for job
     *
     * @param string $jobDirectory The job directory path
     *
     * @return string The input file path
     */
    private function getInputFilePath(string $jobDirectory): string
    {
        return $jobDirectory . '/' . self::INPUT_FILE_NAME;
    }

    /**
     * Signal runner process to attempt graceful shutdown
     *
     * @param string $jobDirectory The job directory path
     *
     * @return void
     */
    private function requestStop(string $jobDirectory): void
    {
        $stopFile = $jobDirectory . '/stop.flag';
        $this->filesystem->dumpFile($stopFile, (string) time());
    }

    /**
     * Attempt to locate PHP CLI binary
     *
     * @return string The PHP binary path
     */
    private function resolvePhpBinary(): string
    {
        $candidates = [];

        // check if PHP is running as a CLI
        if (PHP_SAPI === 'cli') {
            $candidates[] = PHP_BINARY;
        }

        $candidates[] = PHP_BINDIR . '/php';
        $candidates[] = '/usr/bin/php';
        $candidates[] = '/usr/local/bin/php';

        foreach ($candidates as $candidate) {
            if (is_executable($candidate)) {
                return $candidate;
            }
        }

        return PHP_BINARY;
    }
}
