<?php

namespace App\Tests\Command;

use Exception;
use App\Util\AppUtil;
use App\Manager\TodoManager;
use PHPUnit\Framework\TestCase;
use App\Command\RotateAppSecretCommand;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class RotateAppSecretCommandTest
 *
 * Test cases for execute secret key rotation command
 *
 * @package App\Tests\Command
 */
class RotateAppSecretCommandTest extends TestCase
{
    private CommandTester $commandTester;
    private AppUtil & MockObject $appUtil;
    private RotateAppSecretCommand $command;
    private TodoManager & MockObject $todoManager;

    protected function setUp(): void
    {
        // mock dependencies
        $this->appUtil = $this->createMock(AppUtil::class);
        $this->todoManager = $this->createMock(TodoManager::class);

        // initialize command instance
        $this->command = new RotateAppSecretCommand($this->appUtil, $this->todoManager);
        $this->commandTester = new CommandTester($this->command);
    }

    /**
     * Test execute command successfully
     *
     * @return void
     */
    public function testExecuteSuccess(): void
    {
        // mock old secret value
        $this->appUtil->expects($this->once())->method('getEnvValue')->with('APP_SECRET')->willReturn('old-secret-value');

        // mock generateKey
        $this->appUtil->expects($this->once())->method('generateKey')->with(16)->willReturn('new-secret-value');

        // mock updateEnvValue
        $this->appUtil->expects($this->once())->method('updateEnvValue')->with('APP_SECRET', 'new-secret-value');

        // expect reEncryptTodos to be called
        $this->todoManager->expects($this->once())->method('reEncryptTodos')->with('old-secret-value', 'new-secret-value');

        // execute command
        $exitCode = $this->commandTester->execute([]);

        // get command output
        $output = $this->commandTester->getDisplay();

        // assert output
        $this->assertStringContainsString('APP_SECRET has been rotated successfully', $output);
        $this->assertStringContainsString('Remember: Sessions, remember-me tokens, and encrypted data may become', $output);
        $this->assertStringContainsString('invalid.', $output);
        $this->assertEquals(Command::SUCCESS, $exitCode);
    }

    /**
     * Test execute command with exception
     *
     * @return void
     */
    public function testExecuteThrowsException(): void
    {
        // mock old secret value
        $this->appUtil->expects($this->once())->method('getEnvValue')->with('APP_SECRET')->willReturn('old-secret');

        // mock generateKey to throw exception
        $this->appUtil->method('generateKey')->willThrowException(new Exception('Something went wrong'));

        // expect rotate not to be called
        $this->appUtil->expects($this->never())->method('updateEnvValue');
        $this->todoManager->expects($this->never())->method('reEncryptTodos');

        // execute command
        $exitCode = $this->commandTester->execute([]);

        // get command output
        $output = $this->commandTester->getDisplay();

        // assert output
        $this->assertStringContainsString('Error during rotation: Something went wrong', $output);
        $this->assertEquals(Command::FAILURE, $exitCode);
    }
}
