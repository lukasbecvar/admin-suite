<?php

namespace App\Tests\Command\User;

use Exception;
use App\Manager\AuthManager;
use App\Manager\UserManager;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use App\Command\User\UserPasswordResetCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class UserPasswordResetCommandTest
 *
 * Test cases for execute user password reset command
 *
 * @package App\Tests\Command\User
 */
class UserPasswordResetCommandTest extends TestCase
{
    private CommandTester $commandTester;
    private UserPasswordResetCommand $command;
    private AuthManager & MockObject $authManager;
    private UserManager & MockObject $userManagerMock;

    protected function setUp(): void
    {
        // mock dependencies
        $this->authManager = $this->createMock(AuthManager::class);
        $this->userManagerMock = $this->createMock(UserManager::class);

        // initialize the command
        $this->command = new UserPasswordResetCommand($this->authManager, $this->userManagerMock);
        $this->commandTester = new CommandTester($this->command);
    }

    /**
     * Test execute command with invalid username
     *
     * @return void
     */
    public function testExecuteCommandWhenUsernameIsEmpty(): void
    {
        // execute command
        $exitCode = $this->commandTester->execute(['username' => '']);

        // get command output
        $output = $this->commandTester->getDisplay();

        // assert result
        $this->assertStringContainsString('Username parameter is required', $output);
        $this->assertEquals(Command::FAILURE, $exitCode);
    }

    /**
     * Test execute command with invalid username
     *
     * @return void
     */
    public function testExecuteCommandWhenUsernameIsInvalid(): void
    {
        // execute command
        $exitCode = $this->commandTester->execute(['username' => 1]);

        // get command output
        $output = $this->commandTester->getDisplay();

        // assert result
        $this->assertStringContainsString('Invalid username type provided (must be string)', $output);
        $this->assertEquals(Command::FAILURE, $exitCode);
    }

    /**
     * Test execute command with user not exist
     *
     * @return void
     */
    public function testExecuteCommandWhenUserNotExist(): void
    {
        $username = 'nonexistentuser';

        // mock user manager
        $this->userManagerMock->expects($this->once())
            ->method('checkIfUserExist')->with($username)->willReturn(false);

        // execute command
        $exitCode = $this->commandTester->execute(['username' => $username]);

        // get command output
        $output = $this->commandTester->getDisplay();

        // assert result
        $this->assertStringContainsString('Error username: ' . $username . ' is not registered', $output);
        $this->assertEquals(Command::FAILURE, $exitCode);
    }

    /**
     * Test execute command with error reset password throw exception
     *
     * @return void
     */
    public function testExecuteCommandWithErrorResetPasswordThrowException(): void
    {
        $username = 'testuser';

        // mock user manager
        $this->userManagerMock->expects($this->once())
            ->method('checkIfUserExist')->with($username)->willReturn(true);

        // mock auth manager
        $this->authManager->expects($this->once())
            ->method('resetUserPassword')->willThrowException(new Exception('Error reset password'));

        // execute command
        $exitCode = $this->commandTester->execute(['username' => $username]);

        // get command output
        $output = $this->commandTester->getDisplay();

        // assert result
        $this->assertStringContainsString('Process error: Error reset password', $output);
        $this->assertEquals(Command::FAILURE, $exitCode);
    }

    /**
     * Test execute command with success reset password
     *
     * @return void
     */
    public function testExecuteCommandWithResetIsSuccess(): void
    {
        $username = 'testuser';

        // mock user manager
        $this->userManagerMock->expects($this->once())
            ->method('checkIfUserExist')->with($username)->willReturn(true);

        // mock auth manager
        $this->authManager->expects($this->once())->method('resetUserPassword');

        // execute command
        $exitCode = $this->commandTester->execute(['username' => $username]);

        // get command output
        $output = $this->commandTester->getDisplay();

        // assert result
        $this->assertStringContainsString('new password is', $output);
        $this->assertEquals(Command::SUCCESS, $exitCode);
    }
}
