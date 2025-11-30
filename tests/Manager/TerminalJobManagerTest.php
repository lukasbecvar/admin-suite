<?php

namespace App\Tests\Manager;

use App\Util\AppUtil;
use RuntimeException;
use App\Util\SessionUtil;
use App\Util\TerminalUtil;
use PHPUnit\Framework\TestCase;
use App\Manager\TerminalJobManager;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Filesystem\Filesystem;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Class TerminalJobManagerTest
 *
 * Test cases for terminal job manager
 *
 * @package App\Tests\Manager
 */
#[CoversClass(TerminalJobManager::class)]
class TerminalJobManagerTest extends TestCase
{
    private string $rootDir;
    private string $jobsBaseDir;
    private Filesystem $filesystem;
    private AppUtil & MockObject $appUtilMock;
    private TerminalJobManager $terminalJobManager;
    private SessionUtil & MockObject $sessionUtilMock;
    private TerminalUtil & MockObject $terminalUtilMock;

    protected function setUp(): void
    {
        // mock dependencies
        $this->filesystem = new Filesystem();
        $this->appUtilMock = $this->createMock(AppUtil::class);
        $this->sessionUtilMock = $this->createMock(SessionUtil::class);
        $this->terminalUtilMock = $this->createMock(TerminalUtil::class);

        // create temporary directories for tests
        $this->rootDir = sys_get_temp_dir() . '/admin-suite-terminal-job-manager-' . uniqid('', true);
        $this->jobsBaseDir = $this->rootDir . '/var/terminal-jobs';
        $this->filesystem->mkdir($this->rootDir, 0700);
        $this->appUtilMock->method('getAppRootDir')->willReturn($this->rootDir);

        // initialize terminal job manager with mocked dependencies
        $this->terminalJobManager = new TerminalJobManager(
            $this->appUtilMock,
            $this->filesystem,
            $this->sessionUtilMock,
            $this->terminalUtilMock
        );
    }

    protected function tearDown(): void
    {
        if (isset($this->filesystem) && $this->filesystem->exists($this->rootDir)) {
            $this->filesystem->remove($this->rootDir);
        }
    }

    /**
     * Create job directory for provided session and job identifiers
     *
     * @param string $sessionId The session identifier
     * @param string $jobId The job identifier
     *
     * @return string
     */
    private function createJobDirectory(string $sessionId, string $jobId): string
    {
        $jobDirectory = sprintf('%s/%s/%s', $this->jobsBaseDir, $sessionId, $jobId);
        $this->filesystem->mkdir($jobDirectory, 0700);

        return $jobDirectory;
    }

    /**
     * Test append input when input channel is not available
     *
     * @return void
     */
    public function testAppendInputWhenChannelIsMissingThrowsException(): void
    {
        $sessionId = 'session123';
        $jobId = 'job123';
        $jobDirectory = $this->createJobDirectory($sessionId, $jobId);

        // mock session ID and terminal util
        $this->sessionUtilMock->method('getSessionId')->willReturn($sessionId);
        $this->terminalUtilMock->method('sanitizeIdentifier')->willReturn($sessionId);
        $this->terminalUtilMock->method('getJobDirectory')->willReturn($jobDirectory);
        $this->terminalUtilMock->method('getInputFilePath')->willReturn($jobDirectory . '/non-existent-file');

        // expect exception
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Terminal job input channel is not available.');

        // call tested method
        $this->terminalJobManager->appendInput($jobId, 'test');
    }

    /**
     * Test append input writes normalized payload to input queue
     *
     * @return void
     */
    public function testAppendInputWritesNormalizedInputToChannel(): void
    {
        $sessionId = 'session456';
        $jobId = 'job456';
        $jobDirectory = $this->createJobDirectory($sessionId, $jobId);
        $inputFile = $jobDirectory . '/input.queue';

        $this->sessionUtilMock->method('getSessionId')->willReturn($sessionId);
        $this->terminalUtilMock->method('sanitizeIdentifier')->willReturn($sessionId);
        $this->terminalUtilMock->method('getJobDirectory')->willReturn($jobDirectory);
        $this->terminalUtilMock->method('getInputFilePath')->willReturn($inputFile);

        $this->filesystem->dumpFile($inputFile, '');

        // call tested method
        $this->terminalJobManager->appendInput($jobId, "first line\r\nsecond line");

        // assert input written to input queue
        $contents = file_get_contents($inputFile);
        $this->assertSame("first line\nsecond line\n", $contents);
    }

    /**
     * Test get output when log file does not exist
     *
     * @return void
     */
    public function testGetOutputWhenLogFileDoesNotExistThrowsException(): void
    {
        $sessionId = 'session789';
        $jobId = 'job789';
        $jobDirectory = $this->createJobDirectory($sessionId, $jobId);

        // mock session ID and terminal util
        $this->sessionUtilMock->method('getSessionId')->willReturn($sessionId);
        $this->terminalUtilMock->method('sanitizeIdentifier')->willReturn($sessionId);
        $this->terminalUtilMock->method('getJobDirectory')->willReturn($jobDirectory);

        // expect exception
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Job output file not found.');

        // call tested method
        $this->terminalJobManager->getOutput($jobId);
    }

    /**
     * Test get output reads chunk and returns metadata
     *
     * @return void
     */
    public function testGetOutputReadsChunkAndReturnsMetadata(): void
    {
        $sessionId = 'session999';
        $jobId = 'job999';
        $jobDirectory = $this->createJobDirectory($sessionId, $jobId);
        $logFile = $jobDirectory . '/output.log';
        $exitCodeFile = $jobDirectory . '/exit-code';
        $meta = [
            'started_at' => 1700000000,
            'execution_mode' => 'interactive-runner'
        ];

        $this->sessionUtilMock->method('getSessionId')->willReturn($sessionId);
        $this->terminalUtilMock->method('sanitizeIdentifier')->willReturn($sessionId);
        $this->terminalUtilMock->method('getJobDirectory')->willReturn($jobDirectory);
        $this->terminalUtilMock->method('getPid')->willReturn(12345);
        $this->terminalUtilMock->method('readMeta')->willReturn($meta);
        $this->terminalUtilMock->method('isProcessRunning')->willReturn(false);

        $this->filesystem->dumpFile($logFile, 'FirstLineSecondLine');
        $this->filesystem->dumpFile($exitCodeFile, '0');

        // call tested method
        $result = $this->terminalJobManager->getOutput($jobId, 0, 5);

        // assert result
        $this->assertSame('First', $result['chunk']);
        $this->assertSame(5, $result['offset']);
        $this->assertFalse($result['isRunning']);
        $this->assertSame(0, $result['exitCode']);
        $this->assertSame(1700000000, $result['startedAt']);
        $this->assertSame('interactive-runner', $result['executionMode']);
    }

    /**
     * Test stop job creates stop flag when job directory exists
     *
     * @return void
     */
    public function testStopJobCreatesStopFlagWhenJobExists(): void
    {
        $sessionId = 'sessionstop';
        $jobId = 'jobstop';
        $jobDirectory = $this->createJobDirectory($sessionId, $jobId);

        $this->sessionUtilMock->method('getSessionId')->willReturn($sessionId);
        $this->terminalUtilMock->method('sanitizeIdentifier')->willReturn($sessionId);
        $this->terminalUtilMock->method('getJobDirectory')->willReturn($jobDirectory);
        $this->terminalUtilMock->expects($this->once())->method('requestStop')->with($jobDirectory);

        // call tested method
        $this->terminalJobManager->stopJob($jobId);
    }
}
