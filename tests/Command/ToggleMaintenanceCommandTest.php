<?php

namespace App\Tests\Command;

use App\Util\AppUtil;
use PHPUnit\Framework\TestCase;
use App\Command\ToggleMaintenanceCommand;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class ToggleMaintenanceCommandTest
 *
 * Test cases for ToggleMaintenanceCommand
 *
 * @package App\Tests\Command
 */
class ToggleMaintenanceCommandTest extends TestCase
{
    private CommandTester $commandTester;
    private AppUtil & MockObject $appUtil;
    private ToggleMaintenanceCommand $command;

    protected function setUp(): void
    {
        // mock dependencies
        $this->appUtil = $this->createMock(AppUtil::class);

        // initialize command instance
        $this->command = new ToggleMaintenanceCommand($this->appUtil);
        $this->commandTester = new CommandTester($this->command);
    }

    /**
     * Test execute command with empty mode
     *
     * @return void
     */
    public function testExecuteWithEmptyMode(): void
    {
        // execute command
        $exitCode = $this->commandTester->execute(['mode' => '']);

        // get command output
        $output = $this->commandTester->getDisplay();

        // assert result
        $this->assertStringContainsString('mode parameter is required', $output);
        $this->assertEquals(Command::FAILURE, $exitCode);
    }

    /**
     * Test execute command with invalid mode type
     *
     * @return void
     */
    public function testExecuteWithInvalidModeType(): void
    {
        // execute command
        $exitCode = $this->commandTester->execute(['mode' => 123]);

        // get command output
        $output = $this->commandTester->getDisplay();

        // assert result
        $this->assertStringContainsString('Invalid mode type provided (must be string)', $output);
        $this->assertEquals(Command::FAILURE, $exitCode);
    }

    /**
     * Test execute command with invalid mode value
     *
     * @return void
     */
    public function testExecuteWithInvalidModeValue(): void
    {
        // execute command
        $exitCode = $this->commandTester->execute(['mode' => 'maybe']);

        // get command output
        $output = $this->commandTester->getDisplay();

        // assert result
        $this->assertStringContainsString('Invalid mode provided (must be true or false)', $output);
        $this->assertEquals(Command::FAILURE, $exitCode);
    }

    /**
     * Test execute command with valid mode
     *
     * @return void
     */
    public function testExecuteWithValidMode(): void
    {
        // mock update env value
        $this->appUtil->expects($this->once())->method('updateEnvValue')
            ->with('MAINTENANCE_MODE', 'true');

        // execute command
        $exitCode = $this->commandTester->execute(['mode' => 'true']);
        $output = $this->commandTester->getDisplay();

        // assert result
        $this->assertStringContainsString('MAINTENANCE_MODE in .env has been set to: true', $output);
        $this->assertEquals(Command::SUCCESS, $exitCode);
    }

    /**
     * Test execute command with exception
     *
     * @return void
     */
    public function testExecuteThrowsException(): void
    {
        // mock update env value
        $this->appUtil->method('updateEnvValue')
            ->willThrowException(new \Exception('Something went wrong'));

        // execute command
        $exitCode = $this->commandTester->execute(['mode' => 'true']);

        // get command output
        $output = $this->commandTester->getDisplay();

        // assert result
        $this->assertStringContainsString('Process error: Something went wrong', $output);
        $this->assertEquals(Command::FAILURE, $exitCode);
    }
}
