<?php

namespace App\Manager;

use App\Util\AppUtil;
use RuntimeException;
use App\Util\SessionUtil;
use App\Util\TerminalUtil;
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

    private AppUtil $appUtil;
    private Filesystem $filesystem;
    private SessionUtil $sessionUtil;
    private TerminalUtil $terminalUtil;

    public function __construct(
        AppUtil $appUtil,
        Filesystem $filesystem,
        SessionUtil $sessionUtil,
        TerminalUtil $terminalUtil
    ) {
        $this->appUtil = $appUtil;
        $this->filesystem = $filesystem;
        $this->sessionUtil = $sessionUtil;
        $this->terminalUtil = $terminalUtil;

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
        $sessionId = $this->terminalUtil->sanitizeIdentifier($this->sessionUtil->getSessionId());
        $jobDirectory = $this->terminalUtil->getJobDirectory($sessionId, $jobId);

        $logFile = $jobDirectory . '/output.log';
        $exitCodeFile = $jobDirectory . '/exit-code';
        $pidFile = $jobDirectory . '/pid';
        $metaFile = $jobDirectory . '/meta.json';
        $inputFile = $this->terminalUtil->getInputFilePath($jobDirectory);

        // create log file upfront so permissions remain accessible by PHP user
        $this->filesystem->touch($logFile);
        $this->filesystem->touch($exitCodeFile);
        $this->filesystem->dumpFile($inputFile, '');
        $maxRuntime = $this->terminalUtil->getConfiguredMaxRuntime();

        $startedAt = time();
        $metaData = json_encode([
            'command' => $command,
            'user' => $sudoUser,
            'working_directory' => $workingDirectory,
            'started_at' => $startedAt,
            'execution_mode' => 'interactive-runner',
            'max_runtime' => $maxRuntime
        ], JSON_PRETTY_PRINT);

        // check if metadata encoding was successful
        if ($metaData === false) {
            throw new RuntimeException('Failed to encode terminal job metadata.');
        }

        // write metadata to file
        $this->filesystem->dumpFile($metaFile, $metaData);

        // run command process
        $runnerCommand = $this->terminalUtil->buildRunnerCommand($jobDirectory);
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
        $pid = $this->terminalUtil->waitForPid($pidFile, 20, 100000);
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
        $sessionId = $this->terminalUtil->sanitizeIdentifier($this->sessionUtil->getSessionId());
        $jobDirectory = $this->terminalUtil->getJobDirectory($sessionId, $jobId, false);
        $logFile = $jobDirectory . '/output.log';
        $exitCodeFile = $jobDirectory . '/exit-code';
        $pid = $this->terminalUtil->getPid($jobDirectory);
        $meta = $this->terminalUtil->readMeta($jobDirectory);

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

        $isRunning = $this->terminalUtil->isProcessRunning($pid);
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
        $sessionId = $this->terminalUtil->sanitizeIdentifier($this->sessionUtil->getSessionId());
        $jobDirectory = $this->terminalUtil->getJobDirectory($sessionId, $jobId, false);
        $inputFile = $this->terminalUtil->getInputFilePath($jobDirectory);

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
            $sessionId = $this->terminalUtil->sanitizeIdentifier($this->sessionUtil->getSessionId());
        } catch (RuntimeException) {
            return;
        }

        try {
            $jobDirectory = $this->terminalUtil->getJobDirectory($sessionId, $jobId, false);
        } catch (RuntimeException) {
            return;
        }

        $this->terminalUtil->requestStop($jobDirectory);
        $pid = $this->terminalUtil->getPid($jobDirectory);

        if ($pid === null) {
            return;
        }

        if (!$this->terminalUtil->isProcessRunning($pid)) {
            return;
        }

        if (function_exists('posix_kill')) {
            @posix_kill($pid, SIGTERM);
        } else {
            $killProcess = Process::fromShellCommandline(sprintf('kill -TERM %d', $pid));
            $killProcess->run();
        }
    }
}
