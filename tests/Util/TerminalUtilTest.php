<?php

namespace App\Tests\Util;

use App\Util\AppUtil;
use RuntimeException;
use App\Util\TerminalUtil;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Filesystem\Filesystem;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Class TerminalUtilTest
 *
 * Test cases for terminal util
 *
 * @package App\Tests\Util
 */
#[CoversClass(TerminalUtil::class)]
class TerminalUtilTest extends TestCase
{
    private string $rootDir;
    private string $jobsBaseDir;
    private Filesystem $filesystem;
    private TerminalUtil $terminalUtil;
    private AppUtil & MockObject $appUtilMock;

    protected function setUp(): void
    {
        // mock dependencies
        $this->filesystem = new Filesystem();
        $this->appUtilMock = $this->createMock(AppUtil::class);

        $this->rootDir = sys_get_temp_dir() . '/admin-suite-terminal-util-' . uniqid('', true);
        $this->jobsBaseDir = $this->rootDir . '/var/terminal-jobs';
        $this->filesystem->mkdir($this->jobsBaseDir, 0700);
        $this->appUtilMock->method('getAppRootDir')->willReturn($this->rootDir);

        // initialize terminal util instance
        $this->terminalUtil = new TerminalUtil($this->filesystem, $this->appUtilMock);
    }

    protected function tearDown(): void
    {
        if (isset($this->filesystem) && $this->filesystem->exists($this->rootDir)) {
            $this->filesystem->remove($this->rootDir);
        }
        unset($_ENV['TERMINAL_JOB_MAX_RUNTIME'], $_SERVER['TERMINAL_JOB_MAX_RUNTIME']);
    }

    /**
     * Test sanitize identifier with clean string
     *
     * @return void
     */
    public function testSanitizeIdentifierWithCleanString(): void
    {
        // call tested method
        $result = $this->terminalUtil->sanitizeIdentifier('clean-string_123');

        // assert result
        $this->assertSame('clean-string_123', $result);
    }

    /**
     * Test sanitize identifier with special chars
     *
     * @return void
     */
    public function testSanitizeIdentifierWithSpecialChars(): void
    {
        // call tested method
        $result = $this->terminalUtil->sanitizeIdentifier('dirty/string!@#$%^-123');

        // assert result
        $this->assertSame('dirtystring-123', $result);
    }

    /**
     * Test sanitize identifier with empty result throws exception
     *
     * @return void
     */
    public function testSanitizeIdentifierWithEmptyResultThrowsException(): void
    {
        // expect exception
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unable to build terminal job identifier.');

        // call tested method
        $this->terminalUtil->sanitizeIdentifier('!@#$%');
    }

    /**
     * Test get job directory creates dir
     *
     * @return void
     */
    public function testGetJobDirectoryCreatesDir(): void
    {
        // call tested method
        $path = $this->terminalUtil->getJobDirectory('session1', 'job1');

        // assert result
        $this->assertDirectoryExists($path);
        $this->assertSame($this->jobsBaseDir . '/session1/job1', $path);
    }

    /**
     * Test get job directory returns existing path
     *
     * @return void
     */
    public function testGetJobDirectoryReturnsExistingPath(): void
    {
        // create existing directory
        $expectedPath = $this->jobsBaseDir . '/session2/job2';
        $this->filesystem->mkdir($expectedPath);

        // call tested method
        $path = $this->terminalUtil->getJobDirectory('session2', 'job2');

        // assert result
        $this->assertSame($expectedPath, $path);
    }

    /**
     * Test get job directory throws exception when dir not found
     *
     * @return void
     */
    public function testGetJobDirectoryThrowsExceptionWhenDirNotFound(): void
    {
        // expect exception
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Terminal job not found.');

        // call tested method
        $this->terminalUtil->getJobDirectory('session3', 'job3', false);
    }

    /**
     * Test get pid returns null when file missing
     *
     * @return void
     */
    public function testGetPidReturnsNullWhenFileMissing(): void
    {
        $jobDirectory = $this->jobsBaseDir . '/session-pid/job-pid';
        $this->filesystem->mkdir($jobDirectory, 0700);
        $this->assertNull($this->terminalUtil->getPid($jobDirectory));
    }

    /**
     * Test get pid returns null when file empty or invalid
     *
     * @return void
     */
    public function testGetPidReturnsNullWhenFileEmptyOrInvalid(): void
    {
        $jobDirectory = $this->jobsBaseDir . '/session-pid/job-pid';
        $this->filesystem->mkdir($jobDirectory, 0700);
        $pidFile = $jobDirectory . '/pid';

        // call tested method with empty file content
        $this->filesystem->dumpFile($pidFile, '');
        $this->assertNull($this->terminalUtil->getPid($jobDirectory));

        // call tested method with invalid file content
        $this->filesystem->dumpFile($pidFile, 'not-a-pid');
        $this->assertNull($this->terminalUtil->getPid($jobDirectory));
    }

    /**
     * Test get pid returns pid
     *
     * @return void
     */
    public function testGetPidReturnsPid(): void
    {
        $jobDirectory = $this->jobsBaseDir . '/session-pid/job-pid';
        $this->filesystem->mkdir($jobDirectory, 0700);
        $pidFile = $jobDirectory . '/pid';

        $this->filesystem->dumpFile($pidFile, '12345');
        $this->assertSame(12345, $this->terminalUtil->getPid($jobDirectory));
    }

    /**
     * Test is process running with invalid pid
     *
     * @return void
     */
    public function testIsProcessRunningWithInvalidPid(): void
    {
        $this->assertFalse($this->terminalUtil->isProcessRunning(null));
        $this->assertFalse($this->terminalUtil->isProcessRunning(0));
        $this->assertFalse($this->terminalUtil->isProcessRunning(-1));
    }

    /**
     * Test read meta returns empty array when file missing
     *
     * @return void
     */
    public function testReadMetaReturnsEmptyArrayWhenFileMissing(): void
    {
        $jobDirectory = $this->jobsBaseDir . '/session-meta/job-meta';
        $this->filesystem->mkdir($jobDirectory, 0700);
        $this->assertSame([], $this->terminalUtil->readMeta($jobDirectory));
    }

    /**
     * Test read meta returns empty array when file empty or invalid
     *
     * @return void
     */
    public function testReadMetaReturnsEmptyArrayWhenFileEmptyOrInvalid(): void
    {
        $jobDirectory = $this->jobsBaseDir . '/session-meta/job-meta';
        $this->filesystem->mkdir($jobDirectory, 0700);
        $metaFile = $jobDirectory . '/meta.json';

        $this->filesystem->dumpFile($metaFile, '');
        $this->assertSame([], $this->terminalUtil->readMeta($jobDirectory));

        $this->filesystem->dumpFile($metaFile, 'not-json');
        $this->assertSame([], $this->terminalUtil->readMeta($jobDirectory));
    }

    /**
     * Test read meta returns decoded array
     *
     * @return void
     */
    public function testReadMetaReturnsDecodedArray(): void
    {
        $jobDirectory = $this->jobsBaseDir . '/session-meta/job-meta';
        $this->filesystem->mkdir($jobDirectory, 0700);
        $metaFile = $jobDirectory . '/meta.json';
        $data = ['key' => 'value', 'nested' => ['a' => 1]];

        $encodedData = json_encode($data);
        $this->assertIsString($encodedData, 'json_encode should return a string.');

        $this->filesystem->dumpFile($metaFile, $encodedData);
        $this->assertSame($data, $this->terminalUtil->readMeta($jobDirectory));
    }

    /**
     * Test build runner command
     *
     * @return void
     */
    public function testBuildRunnerCommand(): void
    {
        $jobDirectory = '/path/to/job';
        $phpBinary = $this->terminalUtil->resolvePhpBinary();
        $runnerScript = $this->rootDir . '/bin/terminal-job-runner.php';

        $expectedCommand = sprintf(
            'bash -lc %s',
            escapeshellarg(sprintf(
                'cd %s && %s %s --job=%s',
                escapeshellarg($this->rootDir),
                escapeshellarg($phpBinary),
                escapeshellarg($runnerScript),
                escapeshellarg($jobDirectory)
            ))
        );
        $expectedCommand = sprintf('%s > /dev/null 2>&1 & echo $!', $expectedCommand);
        $this->assertSame($expectedCommand, $this->terminalUtil->buildRunnerCommand($jobDirectory));
    }

    /**
     * Test get input file path
     *
     * @return void
     */
    public function testGetInputFilePath(): void
    {
        $jobDirectory = '/path/to/job';
        $this->assertSame('/path/to/job/input.queue', $this->terminalUtil->getInputFilePath($jobDirectory));
    }

    /**
     * Test request stop
     *
     * @return void
     */
    public function testRequestStop(): void
    {
        $jobDirectory = $this->jobsBaseDir . '/session-stop/job-stop';
        $this->filesystem->mkdir($jobDirectory, 0700);
        $stopFile = $jobDirectory . '/stop.flag';

        // call tested method
        $this->terminalUtil->requestStop($jobDirectory);

        // assert result
        $this->assertFileExists($stopFile);
        $this->assertNotEmpty(file_get_contents($stopFile));
    }

    /**
     * Test get configured max runtime
     *
     * @return void
     */
    public function testGetConfiguredMaxRuntime(): void
    {
        // test with $_ENV
        $_ENV['TERMINAL_JOB_MAX_RUNTIME'] = '3600';
        $this->assertSame(3600, $this->terminalUtil->getConfiguredMaxRuntime());

        // test with $_SERVER
        unset($_ENV['TERMINAL_JOB_MAX_RUNTIME']);
        $_SERVER['TERMINAL_JOB_MAX_RUNTIME'] = '1800';
        $this->assertSame(1800, $this->terminalUtil->getConfiguredMaxRuntime());

        // test with invalid value
        $_SERVER['TERMINAL_JOB_MAX_RUNTIME'] = 'invalid';
        $this->assertNull($this->terminalUtil->getConfiguredMaxRuntime());

        // test with empty value
        $_SERVER['TERMINAL_JOB_MAX_RUNTIME'] = '';
        $this->assertNull($this->terminalUtil->getConfiguredMaxRuntime());

        // test when not set
        unset($_SERVER['TERMINAL_JOB_MAX_RUNTIME']);
        $this->assertNull($this->terminalUtil->getConfiguredMaxRuntime());
    }

    /**
     * Test wait for pid success
     *
     * @return void
     */
    public function testWaitForPidSuccess(): void
    {
        $pidFile = $this->rootDir . '/pid_wait_test';
        $this->filesystem->dumpFile($pidFile, '98765');
        $pid = $this->terminalUtil->waitForPid($pidFile, 5, 1);
        $this->assertSame(98765, $pid);
    }

    /**
     * Test wait for pid failure
     *
     * @return void
     */
    public function testWaitForPidFailure(): void
    {
        $pidFile = $this->rootDir . '/pid_wait_test_fail';
        $pid = $this->terminalUtil->waitForPid($pidFile, 5, 1);
        $this->assertNull($pid);
    }

    /**
     * Test resolve php binary
     *
     * @return void
     */
    public function testResolvePhpBinary(): void
    {
        // call tested method
        $result = $this->terminalUtil->resolvePhpBinary();

        // assert result
        $this->assertNotEmpty($result);
    }
}
