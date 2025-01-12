<?php

namespace App\Tests\Command;

use Exception;
use PHPUnit\Framework\TestCase;
use App\Manager\MonitoringManager;
use PHPUnit\Framework\MockObject\MockObject;
use App\Command\DisableNextMonitoringCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class DisableNextMonitoringCommandTest
 *
 * Test cases for DisableNextMonitoringCommand
 *
 * @package App\Tests\Command
 */
class DisableNextMonitoringCommandTest extends TestCase
{
    private CommandTester $commandTester;
    private DisableNextMonitoringCommand $command;
    private MonitoringManager & MockObject $monitoringManagerMock;

    protected function setUp(): void
    {
        // mock dependencies
        $this->monitoringManagerMock = $this->createMock(MonitoringManager::class);

        // initialize command instance
        $this->command = new DisableNextMonitoringCommand($this->monitoringManagerMock);
        $this->commandTester = new CommandTester($this->command);
    }

    /**
     * Test execute when services monitoring disabled successfully
     *
     * @return void
     */
    public function testExecuteSuccessful(): void
    {
        // expect disable next monitoring method to be called
        $this->monitoringManagerMock->expects($this->once())->method('disableNextMonitoring')
            ->with('monitor-job', 10);

        // execute command
        $exitCode = $this->commandTester->execute(['time' => 10]);

        // get command output
        $output = $this->commandTester->getDisplay();

        // assert response
        $this->assertStringContainsString('Next monitoring disabled (time: 10 minutes)', $output);
        $this->assertSame(Command::SUCCESS, $exitCode);
    }

    /**
     * Test execute with exception thrown by MonitoringManager
     *
     * @return void
     */
    public function testExecuteWithException(): void
    {
        // mock exception
        $this->monitoringManagerMock->expects($this->once())->method('disableNextMonitoring')
            ->with('monitor-job', 10)->willThrowException(new Exception('Unexpected error'));

        // execute command
        $exitCode = $this->commandTester->execute(['time' => 10]);

        // get command output
        $output = $this->commandTester->getDisplay();

        // assert response
        $this->assertStringContainsString('Error to disable monitoring process: Unexpected error', $output);
        $this->assertSame(Command::FAILURE, $exitCode);
    }
}
