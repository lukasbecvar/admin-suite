<?php

namespace App\Tests\Command;

use Exception;
use PHPUnit\Framework\TestCase;
use App\Manager\ServiceManager;
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
    private ServiceManager & MockObject $serviceManagerMock;
    private MonitoringManager & MockObject $monitoringManagerMock;

    protected function setUp(): void
    {
        // mock dependencies
        $this->serviceManagerMock = $this->createMock(ServiceManager::class);
        $this->monitoringManagerMock = $this->createMock(MonitoringManager::class);

        // initialize command instance
        $this->command = new DisableNextMonitoringCommand($this->serviceManagerMock, $this->monitoringManagerMock);
        $this->commandTester = new CommandTester($this->command);
    }

    /**
     * Test execute when services monitoring disabled successfully
     *
     * @return void
     */
    public function testExecuteSuccessful(): void
    {
        // mock services list
        $services = [
            ['service_name' => 'service1', 'monitoring' => true],
            ['service_name' => 'service2', 'monitoring' => true],
        ];
        $this->serviceManagerMock->method('getServicesList')->willReturn($services);
        $this->monitoringManagerMock->expects($this->exactly(2))->method('disableNextMonitoring')->willReturnCallback(function ($serviceName, $time) {
            static $callIndex = 0;
            $expectedCalls = [
                ['service1', 10],
                ['service2', 10],
            ];
            $this->assertSame($expectedCalls[$callIndex][0], $serviceName);
            $this->assertSame($expectedCalls[$callIndex][1], $time);
            $callIndex++;
        });

        // execute command
        $exitCode = $this->commandTester->execute(['time' => 10]);

        // get command output
        $output = $this->commandTester->getDisplay();

        // assert response
        $this->assertStringContainsString('next monitoring disabled for all services (for next 10 minutes)', $output);
        $this->assertSame(Command::SUCCESS, $exitCode);
    }

    /**
     * Test execute when services list is not iterable
     *
     * @return void
     */
    public function testExecuteWithInvalidServicesList(): void
    {
        $this->serviceManagerMock->method('getServicesList')->willReturn(null);

        // execute command
        $exitCode = $this->commandTester->execute(['time' => 10]);

        // get command output
        $output = $this->commandTester->getDisplay();

        // assert response
        $this->assertStringContainsString('error to get services list', $output);
        $this->assertSame(Command::FAILURE, $exitCode);
    }

    /**
     * Test execute with exception thrown by MonitoringManager
     *
     * @return void
     */
    public function testExecuteWithException(): void
    {
        // mock services list
        $this->serviceManagerMock->method('getServicesList')->willReturn([
            ['service_name' => 'service1', 'monitoring' => true]
        ]);

        // mock disable next monitoring method
        $this->monitoringManagerMock->method('disableNextMonitoring')
            ->willThrowException(new Exception('Unexpected error'));

        // execute command
        $exitCode = $this->commandTester->execute(['time' => 10]);

        // get command output
        $output = $this->commandTester->getDisplay();

        // assert response
        $this->assertStringContainsString('Error to disable next monitoring: Unexpected error', $output);
        $this->assertSame(Command::FAILURE, $exitCode);
    }
}
