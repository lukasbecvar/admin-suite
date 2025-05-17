<?php

namespace App\Tests\Command\Log;

use DateTime;
use App\Entity\Log;
use App\Manager\LogManager;
use App\Manager\UserManager;
use App\Util\VisitorInfoUtil;
use PHPUnit\Framework\TestCase;
use App\Command\Log\LogReaderCommand;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class LogReaderCommandTest
 *
 * Test cases for execute log reader command
 *
 * @package App\Tests\Command\Log
 */
class LogReaderCommandTest extends TestCase
{
    private LogReaderCommand $command;
    private CommandTester $commandTester;
    private LogManager & MockObject $logManager;
    private UserManager & MockObject $userManager;
    private VisitorInfoUtil & MockObject $visitorInfoUtil;

    protected function setUp(): void
    {
        // mock dependencies
        $this->logManager = $this->createMock(LogManager::class);
        $this->userManager = $this->createMock(UserManager::class);
        $this->visitorInfoUtil = $this->createMock(VisitorInfoUtil::class);

        // create command instance
        $this->command = new LogReaderCommand(
            $this->logManager,
            $this->userManager,
            $this->visitorInfoUtil
        );

        // create command tester instance
        $this->commandTester = new CommandTester($this->command);
    }

    /**
     * Test execute command with invalid status
     *
     * @return void
     */
    public function testExecuteCommandWithInvalidStatus(): void
    {
        // execute command with empty status
        $exitCode = $this->commandTester->execute(['status' => '']);

        // get command output
        $output = $this->commandTester->getDisplay();

        // assert result
        $this->assertStringContainsString('Status parameter is required', $output);
        $this->assertEquals(Command::FAILURE, $exitCode);
    }

    /**
     * Test execute command with non-string status
     *
     * @return void
     */
    public function testExecuteCommandWithNonStringStatus(): void
    {
        // execute command with non-string status
        $exitCode = $this->commandTester->execute(['status' => 1]);

        // get command output
        $output = $this->commandTester->getDisplay();

        // assert result
        $this->assertStringContainsString('Invalid status type provided (must be string)', $output);
        $this->assertEquals(Command::FAILURE, $exitCode);
    }

    /**
     * Test execute command with valid status
     *
     * @return void
     */
    public function testExecuteCommandWithValidStatus(): void
    {
        // mock log object
        $log = $this->createMock(Log::class);
        $log->method('getId')->willReturn(1);
        $log->method('getName')->willReturn('Log name');
        $log->method('getMessage')->willReturn('Log message');
        $log->method('getTime')->willReturn(new DateTime());
        $log->method('getUserAgent')->willReturn('User agent string');
        $log->method('getIpAddress')->willReturn('127.0.0.1');
        $log->method('getUserId')->willReturn(1);

        // mock log manager
        $this->logManager->method('getLogsWhereStatus')->willReturn([$log]);
        $this->logManager->method('getLogsCountWhereStatus')->willReturn(1);

        // mock user manager
        $this->userManager->method('getUsernameById')->willReturn('Test User');

        // mock visitor info util
        $this->visitorInfoUtil->method('getBrowserShortify')->willReturn('Browser');
        $this->visitorInfoUtil->method('getOs')->willReturn('OS');

        // execute command
        $exitCode = $this->commandTester->execute(['status' => 'all']);

        // get command output
        $output = $this->commandTester->getDisplay();

        // assert result
        $this->assertStringContainsString('Log name', $output);
        $this->assertStringContainsString('Log message', $output);
        $this->assertStringContainsString('Browser', $output);
        $this->assertStringContainsString('OS', $output);
        $this->assertStringContainsString('127.0.0.1', $output);
        $this->assertStringContainsString('Test User', $output);
        $this->assertEquals(Command::SUCCESS, $exitCode);
    }
}
