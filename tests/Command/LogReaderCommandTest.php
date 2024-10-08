<?php

namespace App\Tests\Command;

use App\Manager\LogManager;
use App\Manager\UserManager;
use App\Util\VisitorInfoUtil;
use PHPUnit\Framework\TestCase;
use App\Command\LogReaderCommand;
use Symfony\Component\Console\Application;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class LogReaderCommandTest
 *
 * Test the log reader command
 *
 * @package App\Tests\Command
 */
class LogReaderCommandTest extends TestCase
{
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

        $command = new LogReaderCommand(
            $this->logManager,
            $this->userManager,
            $this->visitorInfoUtil
        );

        // get application instance and add command
        $application = new Application();
        $application->add($command);
        $command = $application->find('app:log:reader');

        // create command tester instance
        $this->commandTester = new CommandTester($command);
    }

    /**
     * Test execute with invalid status
     *
     * @return void
     */
    public function testExecuteWithInvalidStatus(): void
    {
        // execute command with empty status
        $this->commandTester->execute(['status' => '']);

        // get output
        $output = $this->commandTester->getDisplay();

        // assert output
        $this->assertStringContainsString('status cannot be empty.', $output);
        $this->assertEquals(Command::FAILURE, $this->commandTester->getStatusCode());
    }

    /**
     * Test execute with valid status
     *
     * @return void
     */
    public function testExecuteWithValidStatus(): void
    {
        // mock data
        $log = $this->createMock(\App\Entity\Log::class);
        $log->method('getId')->willReturn(1);
        $log->method('getName')->willReturn('Log name');
        $log->method('getMessage')->willReturn('Log message');
        $log->method('getTime')->willReturn(new \DateTime());
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
        $this->commandTester->execute(['status' => 'all']);

        // get output
        $output = $this->commandTester->getDisplay();

        // assert output
        $this->assertStringContainsString('Log name', $output);
        $this->assertStringContainsString('Log message', $output);
        $this->assertStringContainsString('Browser', $output);
        $this->assertStringContainsString('OS', $output);
        $this->assertStringContainsString('127.0.0.1', $output);
        $this->assertStringContainsString('Test User', $output);
        $this->assertEquals(Command::SUCCESS, $this->commandTester->getStatusCode());
    }
}
