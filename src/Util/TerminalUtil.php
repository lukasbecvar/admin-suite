<?php

namespace App\Util;

use RuntimeException;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Class TerminalUtil
 *
 * Util with helper methods for terminal jobs
 *
 * @package App\Util
 */
class TerminalUtil
{
    private AppUtil $appUtil;
    private Filesystem $filesystem;

    public function __construct(Filesystem $filesystem, AppUtil $appUtil)
    {
        $this->filesystem = $filesystem;
        $this->appUtil = $appUtil;
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
    public function getJobDirectory(string $sessionId, string $jobId, bool $autoCreate = true): string
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
    public function getPid(string $jobDirectory): ?int
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
    public function isProcessRunning(?int $pid): bool
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
    public function sanitizeIdentifier(string $value): string
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
    public function readMeta(string $jobDirectory): array
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
    public function buildRunnerCommand(string $jobDirectory): string
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
    public function getConfiguredMaxRuntime(): ?int
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
    public function waitForPid(string $pidFile, int $attempts, int $sleepMicroseconds): ?int
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
    public function getInputFilePath(string $jobDirectory): string
    {
        return $jobDirectory . '/input.queue';
    }

    /**
     * Signal runner process to attempt graceful shutdown
     *
     * @param string $jobDirectory The job directory path
     *
     * @return void
     */
    public function requestStop(string $jobDirectory): void
    {
        $stopFile = $jobDirectory . '/stop.flag';
        $this->filesystem->dumpFile($stopFile, (string) time());
    }

    /**
     * Attempt to locate PHP CLI binary
     *
     * @return string The PHP binary path
     */
    public function resolvePhpBinary(): string
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
