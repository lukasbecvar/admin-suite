<?php

namespace App\Tests\Command;

use App\Util\ServerUtil;
use PHPUnit\Framework\TestCase;
use App\Manager\DatabaseManager;
use App\Command\RequirementsCheckCommand;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class RequirementsCheckCommandTest
 *
 * Test cases for execute requirements check command
 *
 * @package App\Tests\Command
 */
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

        // create the command
        $this->command = new RequirementsCheckCommand($this->serverUtil, $this->databaseManager);
        $this->commandTester = new CommandTester($this->command);
    }

    /**
     * Test execute requirements check command
     *
     * @return void
     */
    public function testExecuteRequirementsCheckCommand(): void
    {
        // execute the command
        $exitCode = $this->commandTester->execute([]);

        // get command output
        $output = $this->commandTester->getDisplay();

        // assert the output
        $this->assertStringContainsString('Database connected successfully', $output);
        $this->assertEquals(Command::SUCCESS, $exitCode);
    }
}
