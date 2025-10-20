#!/usr/bin/env php
<?php

declare(strict_types=1);

use Symfony\Component\Process\Process;
use Symfony\Component\Process\InputStream;
use Symfony\Component\Process\Exception\ProcessTimedOutException;

require dirname(__DIR__) . '/vendor/autoload.php';

/**
 * Background job runner that executes a terminal job with interactive support
 * 
 * Separate script for running terminal command in the background
 * 
 * @package bin
 */
final class TerminalJobRunner
{
    // default maximum runtime for a terminal job in seconds
    private const DEFAULT_MAX_RUNTIME = 7200;

    private string $logFile;
    private string $pidFile;
    private string $inputFile;
    private string $jobDirectory;
    private string $exitCodeFile;
    private string $stopFlagFile;
    private array $meta = [];

    public function __construct(string $jobDirectory)
    {
        $this->jobDirectory = rtrim($jobDirectory, '/');
        $this->pidFile = $this->jobDirectory . '/pid';
        $this->logFile = $this->jobDirectory . '/output.log';
        $this->inputFile = $this->jobDirectory . '/input.queue';
        $this->exitCodeFile = $this->jobDirectory . '/exit-code';
        $this->stopFlagFile = $this->jobDirectory . '/stop.flag';
    }

    /**
     * Run the terminal job
     * 
     * @throws RuntimeException If the terminal job cannot be started
     *
     * @return int The exit code of the terminal job
     */
    public function run(): int
    {
        $this->ensureEnvironment();
        $command = (string) $this->meta['command'];
        $workingDirectory = (string) ($this->meta['working_directory'] ?? '/');
        $inputStream = new InputStream();

        $commandLine = $this->buildExecutionCommand($command);
        $process = Process::fromShellCommandline($commandLine, $workingDirectory);
        $timeoutSeconds = $this->resolveMaxRuntime();
        $timeoutDeadline = $timeoutSeconds !== null ? microtime(true) + $timeoutSeconds : null;

        // set process timeout
        if ($timeoutSeconds !== null) {
            $process->setTimeout($timeoutSeconds);
        } else {
            $process->setTimeout(null);
        }

        $process->setIdleTimeout(null);
        $process->setInput($inputStream);

        try {
            $process->setPty(true);
        } catch (RuntimeException $exception) {
            new RuntimeException('Terminal job runner failed to set PTY: ' . $exception->getMessage());
        }

        $logHandle = fopen($this->logFile, 'ab');
        if ($logHandle === false) {
            return 1;
        }

        $this->persistPid();
        $this->registerSignalHandlers($process);

        $inputOffset = 0;
        $process->start();
        $timedOut = false;

        try {
            while ($process->isRunning()) {
                $this->drainProcessOutput($process, $logHandle);
                $inputOffset = $this->forwardQueuedInput($inputStream, $inputOffset);
                $this->checkStopRequest($process);
                if ($timeoutDeadline !== null && microtime(true) >= $timeoutDeadline) {
                    $timedOut = true;
                    $this->logTimeout($logHandle, $timeoutSeconds);
                    $process->stop(0);
                    break;
                }

                usleep(50000);
            }
        } catch (ProcessTimedOutException $exception) {
            $timedOut = true;
            $this->logTimeout($logHandle, $timeoutSeconds);
            $process->stop(0);
        }

        $inputStream->close();
        $this->drainProcessOutput($process, $logHandle);
        fclose($logHandle);

        $exitCode = $process->getExitCode();
        if ($exitCode === null) {
            $exitCode = 0;
        }

        if ($timedOut) {
            $exitCode = 124;
        }

        file_put_contents($this->exitCodeFile, (string) $exitCode, LOCK_EX);

        return (int) $exitCode;
    }

    /**
     * Build full command line used to execute the terminal command
     *
     * @param string $command The raw command from metadata
     *
     * @return string The command line for the process component
     */
    private function buildExecutionCommand(string $command): string
    {
        $sudoUser = '';
        if (isset($this->meta['user']) && is_string($this->meta['user'])) {
            $sudoUser = trim($this->meta['user']);
        }

        $escapedCommand = escapeshellarg($command);

        // check if sudo user is empty
        if ($sudoUser === '') {
            return sprintf('bash -lc %s', $escapedCommand);
        }

        return sprintf('sudo -u %s bash -lc %s', escapeshellarg($sudoUser), $escapedCommand);
    }

    /**
     * Ensure environment for running the terminal job
     * 
     * @throws RuntimeException If the terminal job directory is not accessible or the terminal job metadata is missing
     * 
     * @return void
     */
    private function ensureEnvironment(): void
    {
        // check if terminal job directory is accessible
        if (!is_dir($this->jobDirectory)) {
            throw new RuntimeException('Terminal job directory is not accessible.');
        }

        // check if terminal job metadata is present
        $metaFile = $this->jobDirectory . '/meta.json';
        if (!is_file($metaFile)) {
            throw new RuntimeException('Terminal job metadata is missing.');
        }

        // read terminal job metadata
        $contents = file_get_contents($metaFile);
        if ($contents === false) {
            throw new RuntimeException('Unable to read terminal job metadata.');
        }

        // decode terminal job metadata
        $decoded = json_decode($contents, true);
        if (!is_array($decoded) || empty($decoded['command'])) {
            throw new RuntimeException('Invalid terminal job metadata.');
        }

        $this->meta = $decoded;
    }

    /**
     * Persist the process ID of the terminal job
     * 
     * @return void
     */
    private function persistPid(): void
    {
        $pid = getmypid();

        // check if process ID is available
        if ($pid === false) {
            return;
        }

        file_put_contents($this->pidFile, (string) $pid, LOCK_EX);
    }

    /**
     * Register signal handlers for the terminal job
     * 
     * @param Process $process The process object
     * 
     * @return void
     */
    private function registerSignalHandlers(Process $process): void
    {
        if (!function_exists('pcntl_signal') || !function_exists('pcntl_async_signals')) {
            return;
        }

        pcntl_async_signals(true);
        $handler = function () use ($process): void {
            if ($process->isRunning()) {
                $process->signal(SIGTERM);
            }
        };

        pcntl_signal(SIGTERM, $handler);
        pcntl_signal(SIGINT, $handler);
    }

    /**
     * Drain the output of the terminal job process
     * 
     * @param Process $process The process object
     * @param resource $logHandle The log file handle
     * 
     * @return void
     */
    private function drainProcessOutput(Process $process, $logHandle): void
    {
        // write output to log file
        $stdout = $process->getIncrementalOutput();
        if ($stdout !== '') {
            fwrite($logHandle, $stdout);
            fflush($logHandle);
        }

        // write error output to log file
        $stderr = $process->getIncrementalErrorOutput();
        if ($stderr !== '') {
            fwrite($logHandle, $stderr);
            fflush($logHandle);
        }
    }

    /**
     * Forward queued input to the terminal job process
     * 
     * @param InputStream $inputStream The input stream object
     * @param int $offset The current offset in the input stream
     * 
     * @return int The new offset in the input stream
     */
    private function forwardQueuedInput(InputStream $inputStream, int $offset): int
    {
        clearstatcache(true, $this->inputFile);
        $size = @filesize($this->inputFile);

        if ($size === false || $size <= $offset) {
            return $offset;
        }

        $handle = fopen($this->inputFile, 'rb');

        if ($handle === false) {
            return $offset;
        }

        if ($offset > 0) {
            fseek($handle, $offset);
        }

        while (!feof($handle)) {
            $chunk = fread($handle, 8192);
            if ($chunk === false || $chunk === '') {
                break;
            }

            $inputStream->write($chunk);
            $offset += strlen($chunk);
        }

        fclose($handle);
        return $offset;
    }

    /**
     * Check if stop request is received
     * 
     * @param Process $process The process object
     * 
     * @return void
     */
    private function checkStopRequest(Process $process): void
    {
        // check if stop flag file exists
        if (!is_file($this->stopFlagFile)) {
            return;
        }

        // terminate running process
        if ($process->isRunning()) {
            $process->signal(SIGTERM);
        }

        @unlink($this->stopFlagFile);
    }

    /**
     * Resolve maximum runtime for the terminal job
     * 
     * @return int|null The maximum runtime in seconds or null if not set
     */
    private function resolveMaxRuntime(): ?int
    {
        // check if maximum runtime is set
        if (isset($this->meta['max_runtime'])) {
            $maxRuntime = $this->meta['max_runtime'];
            if (is_numeric($maxRuntime)) {
                $seconds = (int) $maxRuntime;

                if ($seconds <= 0) {
                    return null;
                }

                return $seconds;
            }
        }

        // get maximum runtime from environment variables
        $value = getenv('TERMINAL_JOB_MAX_RUNTIME');

        if (($value === false || trim((string) $value) === '') && isset($_SERVER['TERMINAL_JOB_MAX_RUNTIME'])) {
            $value = $_SERVER['TERMINAL_JOB_MAX_RUNTIME'];
        } elseif (($value === false || trim((string) $value) === '') && isset($_ENV['TERMINAL_JOB_MAX_RUNTIME'])) {
            $value = $_ENV['TERMINAL_JOB_MAX_RUNTIME'];
        }

        if ($value === false || trim((string) $value) === '') {
            return self::DEFAULT_MAX_RUNTIME;
        }

        $seconds = (int) $value;

        if ($seconds <= 0) {
            return null;
        }

        return $seconds;
    }

    /**
     * Log timeout message
     * 
     * @param resource $logHandle The log file handle
     * @param int|null $timeoutSeconds The maximum runtime in seconds or null if not set
     * 
     * @return void
     */
    private function logTimeout($logHandle, ?int $timeoutSeconds): void
    {
        $limit = $timeoutSeconds ?? self::DEFAULT_MAX_RUNTIME;
        $timeoutMessage = sprintf("\n[runner] Command exceeded maximum runtime (%d seconds). Terminating.\n", $limit);
        fwrite($logHandle, $timeoutMessage);
        fflush($logHandle);
    }
}

$jobDirectory = null;

foreach ($argv as $argument) {
    if (preg_match('/^--job=(.+)$/', $argument, $matches) === 1) {
        $jobDirectory = $matches[1];
        break;
    }
}

if ($jobDirectory === null && isset($argv[1])) {
    $jobDirectory = $argv[1];
}

if ($jobDirectory === null || $jobDirectory === '') {
    fwrite(STDERR, "Missing required --job argument.\n");
    exit(1);
}

// run terminal job runner
try {
    $runner = new TerminalJobRunner($jobDirectory);
    $exitCode = $runner->run();
    exit($exitCode);
} catch (Throwable $throwable) {
    fwrite(STDERR, 'Terminal job runner failed: ' . $throwable->getMessage() . PHP_EOL);
    exit(1);
}
