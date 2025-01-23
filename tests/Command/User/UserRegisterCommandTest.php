<?php

namespace App\Tests\Command\User;

use Exception;
use App\Manager\UserManager;
use App\Manager\AuthManager;
use PHPUnit\Framework\TestCase;
use App\Command\User\UserRegisterCommand;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class UserRegisterCommandTest
 *
 * Test cases for execute user register command
 *
 * @package App\Tests\Command
 */
class UserRegisterCommandTest extends TestCase
{
    private CommandTester $commandTester;
    private UserRegisterCommand $command;
    private AuthManager & MockObject $authManager;
    private UserManager & MockObject $userManager;

    protected function setUp(): void
    {
        // mock dependencies
        $this->authManager = $this->createMock(AuthManager::class);
        $this->userManager = $this->createMock(UserManager::class);

        // initialize the command
        $this->command = new UserRegisterCommand($this->authManager, $this->userManager);
        $this->commandTester = new CommandTester($this->command);
    }

    /**
     * Test execute command with empty username
     *
     * @return void
     */
    public function testExecuteCommandWithEmptyUsername(): void
    {
        // execute command with empty username
        $exitCode = $this->commandTester->execute(['username' => '']);

        // get command output
        $output = $this->commandTester->getDisplay();

        // assert result
        $this->assertStringContainsString('Username parameter is required', $output);
        $this->assertSame(Command::FAILURE, $exitCode);
    }

    /**
     * Test execute register command with invalid username
     *
     * @return void
     */
    public function testExecuteCommandWithInvalidUsername(): void
    {
        // execute command with invalid username
        $exitCode = $this->commandTester->execute(['username' => 1]);

        // get command output
        $output = $this->commandTester->getDisplay();

        // assert result
        $this->assertStringContainsString('Invalid username type provided (must be string)', $output);
        $this->assertSame(Command::FAILURE, $exitCode);
    }

    /**
     * Test execute register command with username length less than 3
     *
     * @return void
     */
    public function testExecuteCommandWithUsernameLengthLessThanThree(): void
    {
        // mock user manager
        $this->userManager->method('checkIfUserExist')->willReturn(false);
        $this->authManager->method('isUsernameBlocked')->willReturn(false);

        // execute command with username length less than 3
        $exitCode = $this->commandTester->execute(['username' => 'te']);

        // get command output
        $output = $this->commandTester->getDisplay();

        // assert result
        $this->assertStringContainsString('Username length must be between 3 and 155 characters', $output);
        $this->assertSame(Command::FAILURE, $exitCode);
    }

    /**
     * Test execute register command with blocked username
     *
     * @return void
     */
    public function testExecuteCommandWithUsernameBlocked(): void
    {
        // mock user manager
        $this->userManager->method('checkIfUserExist')->willReturn(false);
        $this->authManager->method('isUsernameBlocked')->willReturn(true);

        // execute command with blocked username
        $exitCode = $this->commandTester->execute(['username' => 'testuser']);

        // get command output
        $output = $this->commandTester->getDisplay();

        // assert result
        $this->assertStringContainsString('Error username: testuser is blocked', $output);
        $this->assertSame(Command::FAILURE, $exitCode);
    }

    /**
     * Test execute register command with already existing username
     *
     * @return void
     */
    public function testExecuteCommandWithUsernameAlreadyExists(): void
    {
        // mock user manager
        $this->userManager->method('checkIfUserExist')->willReturn(true);

        // execute command with already existing username
        $exitCode = $this->commandTester->execute(['username' => 'testuser']);

        // get command output
        $output = $this->commandTester->getDisplay();

        // assert result
        $this->assertStringContainsString('Error username: testuser is already used', $output);
        $this->assertSame(Command::FAILURE, $exitCode);
    }

    /**
     * Test execute register command with error register user throw exception
     *
     * @return void
     */
    public function testExecuteCommandWithErrorRegisterUserThrowException(): void
    {
        // mock auth manager
        $this->authManager->expects($this->once())
            ->method('registerUser')->willThrowException(new Exception('Error register user'));

        // execute command with new username
        $exitCode = $this->commandTester->execute(['username' => 'newuser']);

        // get command output
        $output = $this->commandTester->getDisplay();

        // assert result
        $this->assertStringContainsString('Process error: Error register user', $output);
        $this->assertSame(Command::FAILURE, $exitCode);
    }

    /**
     * Test execute register command with success result
     *
     * @return void
     */
    public function testExecuteCommandWithRegisterUserSuccess(): void
    {
        // mock auth manager
        $this->authManager->expects($this->once())->method('registerUser')->with('newuser');

        // execute command with new username
        $exitCode = $this->commandTester->execute(['username' => 'newuser']);

        // get command output
        $output = $this->commandTester->getDisplay();

        // assert result
        $this->assertStringContainsString('New user registered username: newuser', $output);
        $this->assertSame(Command::SUCCESS, $exitCode);
    }
}
