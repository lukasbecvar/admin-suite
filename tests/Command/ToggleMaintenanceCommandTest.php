<?php

namespace App\Tests\Command;

use Exception;
use App\Util\AppUtil;
use PHPUnit\Framework\TestCase;
use App\Command\ToggleMaintenanceCommand;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class ToggleMaintenanceCommandTest
 *
 * Test cases for toggle maintenance command
 *
 * @package App\Tests\Command
 */
class ToggleMaintenanceCommandTest extends TestCase
{
    private CommandTester $commandTester;
    private ToggleMaintenanceCommand $command;
    private AppUtil & MockObject $appUtilMock;

    protected function setUp(): void
    {
        // mock dependencies
        $this->appUtilMock = $this->createMock(AppUtil::class);

        // init command instance
        $this->command = new ToggleMaintenanceCommand($this->appUtilMock);
        $this->commandTester = new CommandTester($this->command);
    }

    /**
     * Test execute toggle maintenance command with exception response
     *
     * @return void
     */
    public function testExecuteCommandWhenExceptionResponse(): void
    {
        // mock getEnvValue to throw an exception
        $this->appUtilMock->method('getEnvValue')
            ->willThrowException(new Exception('Failed to get environment value'));

        // execute command
        $exitCode = $this->commandTester->execute([]);

        // get command output
        $output = $this->commandTester->getDisplay();

        // assert command output
        $this->assertStringContainsString('Process error: Failed to get environment value', $output);
        $this->assertSame(Command::FAILURE, $exitCode);
    }

    /**
     * Test execute toggle maintenance command with maintenance mode enabled
     *
     * @return void
     */
    public function testExecuteCommandWithMaintenanceModeEnabled(): void
    {
        // mock getEnvValue to simulate maintenance mode is enabled
        $this->appUtilMock->method('getEnvValue')->willReturn('true');

        // expect call updateEnvValue with 'false' value
        $this->appUtilMock->expects($this->once())->method('updateEnvValue')
            ->with('MAINTENANCE_MODE', 'false');

        // execute command
        $exitCode = $this->commandTester->execute([]);

        // get command output
        $output = $this->commandTester->getDisplay();

        // assert command output
        $this->assertStringContainsString("MAINTENANCE_MODE in .env has been set to true", $output);
        $this->assertSame(Command::SUCCESS, $exitCode);
    }

    /**
     * Test execute toggle maintenance command with maintenance mode disabled
     *
     * @return void
     */
    public function testExecuteCommandWithMaintenanceModeDisabled(): void
    {
        // mock getEnvValue to simulate maintenance mode is disabled
        $this->appUtilMock->method('getEnvValue')->willReturn('false');

        // expect call updateEnvValue with 'true' value
        $this->appUtilMock->expects($this->once())->method('updateEnvValue')
            ->with('MAINTENANCE_MODE', 'true');

        // execute command
        $exitCode = $this->commandTester->execute([]);

        // get command output
        $output = $this->commandTester->getDisplay();

        // assert command output
        $this->assertStringContainsString("MAINTENANCE_MODE in .env has been set to true", $output);
        $this->assertSame(Command::SUCCESS, $exitCode);
    }
}
