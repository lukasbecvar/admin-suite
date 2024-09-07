<?php

namespace App\Tests\Command\User;

use App\Manager\UserManager;
use App\Manager\AuthManager;
use PHPUnit\Framework\TestCase;
use App\Command\User\UserRegisterCommand;
use Symfony\Component\Console\Application;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class UserRegisterCommandTest
 *
 * Test cases for the UserRegisterCommand class
 *
 * @package App\Tests\Command
 */
class UserRegisterCommandTest extends TestCase
{
    private Application $application;
    private UserRegisterCommand $command;
    private AuthManager & MockObject $authManager;
    private UserManager & MockObject $userManager;

    protected function setUp(): void
    {
        // mock AuthManager
        $this->authManager = $this->createMock(AuthManager::class);

        // mock UserManager
        $this->userManager = $this->createMock(UserManager::class);

        // create the command with the mocked UserManager.
        $this->command = new UserRegisterCommand($this->authManager, $this->userManager);

        // create application and add command
        $this->application = new Application();
        $this->application->add($this->command);
    }

    /**
     * Test case for empty username argument
     *
     * @return void
     */
    public function testEmptyUsername(): void
    {
        // create CommandTester
        $commandTester = new CommandTester($this->command);

        // simulate command execution with empty username
        $commandTester->execute([
            'command' => $this->command->getName(),
            'username' => '',
        ]);

        // get output
        $output = $commandTester->getDisplay();

        // assert error message
        $this->assertStringContainsString('Username cannot be empty.', $output);
        $this->assertSame(Command::FAILURE, $commandTester->getStatusCode());
    }

    /**
     * Test case for username already exists
     *
     * @return void
     */
    public function testUsernameAlreadyExists(): void
    {
        // configure the UserManager mock to return true for existing user
        $this->userManager->method('checkIfUserExist')->willReturn(true);

        // create CommandTester
        $commandTester = new CommandTester($this->application->find('app:user:register'));

        // execute the command with an existing username
        $commandTester->execute(['username' => 'testuser']);

        // get output
        $output = $commandTester->getDisplay();

        // assert output
        $this->assertStringContainsString('Error username: testuser is already used!', $output);
        $this->assertSame(Command::FAILURE, $commandTester->getStatusCode());
    }

    /**
     * Test case for registering a new user successfully
     *
     * @return void
     */
    public function testRegisterUserSuccess(): void
    {
        $this->authManager->expects($this->once())
            ->method('registerUser')
            ->with('newuser');

        // create CommandTester
        $commandTester = new CommandTester($this->command);

        // simulate command execution with arguments
        $commandTester->execute([
            'command' => $this->command->getName(),
            'username' => 'newuser',
        ]);

        // get output
        $output = $commandTester->getDisplay();

        // assert output
        $this->assertStringContainsString('New user registered username: newuser', $output);
        $this->assertSame(Command::SUCCESS, $commandTester->getStatusCode());
    }
}
