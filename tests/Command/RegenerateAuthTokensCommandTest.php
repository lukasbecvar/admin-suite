<?php

namespace App\Tests\Command;

use App\Manager\AuthManager;
use PHPUnit\Framework\TestCase;
use App\Command\RegenerateAuthTokensCommand;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class RegenerateAuthTokensCommandTest
 *
 * Test cases for execute auth tokens regenerate command
 *
 * @package App\Tests\Command
 */
#[CoversClass(RegenerateAuthTokensCommand::class)]
class RegenerateAuthTokensCommandTest extends TestCase
{
    private CommandTester $commandTester;
    private RegenerateAuthTokensCommand $command;
    private AuthManager & MockObject $authManager;

    protected function setUp(): void
    {
        // mock dependencies
        $this->authManager = $this->createMock(AuthManager::class);

        // initialize the command
        $this->command = new RegenerateAuthTokensCommand($this->authManager);
        $this->commandTester = new CommandTester($this->command);
    }

    /**
     * Test execute auth tokens regenerate command with exception
     *
     * @return void
     */
    public function testExecuteCommandWithException(): void
    {
        // mock regenerate tokens method status
        $this->authManager->expects($this->once())->method('regenerateUsersTokens')
            ->willReturn(['status' => false, 'message' => 'Error message']);

        // execute the command
        $exitCode = $this->commandTester->execute([]);

        // get command output
        $output = $this->commandTester->getDisplay();

        // assert result
        $this->assertStringContainsString('Process error: Error message', $output);
        $this->assertSame(Command::FAILURE, $exitCode);
    }

    /**
     * Test execute auth tokens regenerate command
     *
     * @return void
     */
    public function testExecuteCommandWhenRegenerationIsSuccess(): void
    {
        // mock regenerate tokens method status
        $this->authManager->expects($this->once())->method('regenerateUsersTokens')
            ->willReturn(['status' => true]);

        // execute the command
        $exitCode = $this->commandTester->execute([]);

        // get command output
        $output = $this->commandTester->getDisplay();

        // assert result
        $this->assertStringContainsString('All tokens is regenerated', $output);
        $this->assertSame(Command::SUCCESS, $exitCode);
    }
}
