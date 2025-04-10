<?php

namespace App\Tests\Command\Monitoring;

use Exception;
use PHPUnit\Framework\TestCase;
use App\Manager\MonitoringManager;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use App\Command\Monitoring\TemporaryMonitoringDisableCommand;

/**
 * Class TemporaryMonitoringDisableCommandTest
 *
 * Test cases for execute temporary monitoring disable command
 *
 * @package App\Tests\Command\Monitoring
 */
class TemporaryMonitoringDisableCommandTest extends TestCase
{
    private CommandTester $commandTester;
    private TemporaryMonitoringDisableCommand $command;
    private MonitoringManager & MockObject $monitoringManager;

    protected function setUp(): void
    {
        // mock the dependencies
        $this->monitoringManager = $this->createMock(MonitoringManager::class);

        // initialize the command instance
        $this->command = new TemporaryMonitoringDisableCommand($this->monitoringManager);
        $this->commandTester = new CommandTester($this->command);
    }

    /**
     * Test execute command when service name is empty
     *
     * @return void
     */
    public function testExecuteCommandWhenServiceNameIsEmpty(): void
    {
        // execute command
        $exitCode = $this->commandTester->execute(['service-name' => '', 'time' => '']);

        // get command output
        $commandOutput = $this->commandTester->getDisplay();

        // assert result
        $this->assertStringContainsString('Service name parameter is required', $commandOutput);
        $this->assertSame(Command::FAILURE, $exitCode);
    }

    /**
     * Test execute command when time is empty
     *
     * @return void
     */
    public function testExecuteCommandWhenTimeIsEmpty(): void
    {
        // execute command
        $exitCode = $this->commandTester->execute(['service-name' => 'test_service', 'time' => '']);

        // get command output
        $commandOutput = $this->commandTester->getDisplay();

        // assert result
        $this->assertStringContainsString('Time parameter is required', $commandOutput);
        $this->assertSame(Command::FAILURE, $exitCode);
    }

    /**
     * Test execute command when time is not numeric
     *
     * @return void
     */
    public function testExecuteCommandWhenTimeIsNotNumeric(): void
    {
        // execute command
        $exitCode = $this->commandTester->execute(['service-name' => 'test_service', 'time' => 'test_time']);

        // get command output
        $commandOutput = $this->commandTester->getDisplay();

        // assert result
        $this->assertStringContainsString('Time parameter must be numeric', $commandOutput);
        $this->assertSame(Command::FAILURE, $exitCode);
    }

    /**
     * Test execute command when service name is not a string
     *
     * @return void
     */
    public function testExecuteCommandWhenServiceNameIsNotAString(): void
    {
        // execute command
        $exitCode = $this->commandTester->execute(['service-name' => 1, 'time' => '1']);

        // get command output
        $commandOutput = $this->commandTester->getDisplay();

        // assert result
        $this->assertStringContainsString('Invalid service name provided', $commandOutput);
        $this->assertSame(Command::FAILURE, $exitCode);
    }

    /**
     * Test execute command when service is already disabled
     *
     * @return void
     */
    public function testExecuteCommandWhenServiceIsAlreadyDisabled(): void
    {
        // mock get monitoring status method
        $this->monitoringManager->method('getMonitoringStatus')->willReturn('disabled');

        // execute command
        $exitCode = $this->commandTester->execute(['service-name' => 'test_service', 'time' => '1']);

        // get command output
        $commandOutput = $this->commandTester->getDisplay();

        // assert result
        $this->assertStringContainsString('Service is already disabled', $commandOutput);
        $this->assertSame(Command::FAILURE, $exitCode);
    }

    /**
     * Test execute command when response is exception
     *
     * @return void
     */
    public function testExecuteCommandWithException(): void
    {
        // simulate exception
        $this->monitoringManager->method('temporaryDisableMonitoring')->willThrowException(new Exception('Simulated error'));

        // execute command
        $exitCode = $this->commandTester->execute(['service-name' => 'test', 'time' => '1']);

        // get command output
        $commandOutput = $this->commandTester->getDisplay();

        // assert result
        $this->assertStringContainsString('Error to disable service monitoring: Simulated error', $commandOutput);
        $this->assertSame(Command::FAILURE, $exitCode);
    }

    /**
     * Test execute command when response is success
     *
     * @return void
     */
    public function testExecuteCommandWithSuccessResponse(): void
    {
        // execute command
        $exitCode = $this->commandTester->execute(['service-name' => 'test', 'time' => '1']);

        // get command output
        $commandOutput = $this->commandTester->getDisplay();

        // assert result
        $this->assertStringContainsString('Service monitoring is disabled for 1 minutes', $commandOutput);
        $this->assertSame(Command::SUCCESS, $exitCode);
    }
}
