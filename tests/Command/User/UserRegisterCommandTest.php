<?php

namespace App\Tests\Command\User;

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
     * Test execute register command with empty username
     *
     * @return void
     */
    public function testEmptyUsername(): void
    {
        // execute command with empty username
        $exitCode = $this->commandTester->execute(['username' => '']);

        // get command output
        $output = $this->commandTester->getDisplay();

        // assert result
        $this->assertSame(Command::FAILURE, $exitCode);
        $this->assertStringContainsString('Username cannot be empty.', $output);
    }

    /**
     * Test execute register command with already existing username
     *
     * @return void
     */
    public function testUsernameAlreadyExists(): void
    {
        // mock user manager
        $this->userManager->method('checkIfUserExist')->willReturn(true);

        // execute command with already existing username
        $exitCode = $this->commandTester->execute(['username' => 'testuser']);

        // get command output
        $output = $this->commandTester->getDisplay();

        // assert result
        $this->assertSame(Command::FAILURE, $exitCode);
        $this->assertStringContainsString('Error username: testuser is already used!', $output);
    }

    /**
     * Test execute register command with success result
     *
     * @return void
     */
    public function testRegisterUserSuccess(): void
    {
        // mock auth manager
        $this->authManager->expects($this->once())->method('registerUser')->with('newuser');

        // execute command with new username
        $exitCode = $this->commandTester->execute(['username' => 'newuser']);

        // get command output
        $output = $this->commandTester->getDisplay();

        // assert result
        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('New user registered username: newuser', $output);
    }
}
