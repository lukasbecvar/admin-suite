<?php

namespace App\Tests\Command;

use App\Util\ServerUtil;
use PHPUnit\Framework\TestCase;
use App\Manager\DatabaseManager;
use App\Command\RequirementsCheckCommand;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class RequirementsCheckCommandTest
 *
 * Test cases for execute requirements check command
 *
 * @package App\Tests\Command
 */
#[CoversClass(RequirementsCheckCommand::class)]
class RequirementsCheckCommandTest extends TestCase
{
    private CommandTester $commandTester;
    private RequirementsCheckCommand $command;
    private ServerUtil & MockObject $serverUtil;
    private DatabaseManager & MockObject $databaseManager;

    protected function setUp(): void
    {
        // mock dependencies
        $this->serverUtil = $this->createMock(ServerUtil::class);
        $this->databaseManager = $this->createMock(DatabaseManager::class);

        // initialize command instance
        $this->command = new RequirementsCheckCommand($this->serverUtil, $this->databaseManager);
        $this->commandTester = new CommandTester($this->command);
    }

    /**
     * Test execute command with all requirements met
     *
     * @return void
     */
    public function testExecuteWithAllRequirementsMet(): void
    {
        // mock checks
        $this->serverUtil->method('getNotInstalledRequirements')->willReturn([]);
        $this->databaseManager->method('isDatabaseDown')->willReturn(false);

        // execute command
        $exitCode = $this->commandTester->execute([]);

        // get command output
        $output = $this->commandTester->getDisplay();

        // assert result
        $this->assertStringContainsString('All requirements are installed', $output);
        $this->assertStringContainsString('Database connected successfully', $output);
        $this->assertSame(Command::SUCCESS, $exitCode);
    }

    /**
     * Test execute command with missing requirements
     *
     * @return void
     */
    public function testExecuteWithMissingRequirements(): void
    {
        // mock checks
        $this->serverUtil->method('getNotInstalledRequirements')->willReturn(['php', 'composer']);
        $this->databaseManager->method('isDatabaseDown')->willReturn(false);

        // execute command
        $exitCode = $this->commandTester->execute([]);

        // get command output
        $output = $this->commandTester->getDisplay();

        // assert result
        $this->assertStringContainsString('The following requirements are not installed: ', $output);
        $this->assertStringContainsString('Database connected successfully', $output);
        $this->assertSame(Command::SUCCESS, $exitCode);
    }

    /**
     * Test execute command with database connection failure
     *
     * @return void
     */
    public function testExecuteWithDatabaseConnectionFailure(): void
    {
        // mock checks
        $this->serverUtil->method('getNotInstalledRequirements')->willReturn([]);
        $this->databaseManager->method('isDatabaseDown')->willReturn(true);

        // execute command
        $exitCode = $this->commandTester->execute([]);

        // get command output
        $output = $this->commandTester->getDisplay();

        // assert result
        $this->assertStringContainsString('All requirements are installed', $output);
        $this->assertStringContainsString('Database is not connected', $output);
        $this->assertSame(Command::SUCCESS, $exitCode);
    }
}
