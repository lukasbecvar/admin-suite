<?php

namespace App\Tests\Command;

use App\Manager\UserManager;
use PHPUnit\Framework\TestCase;
use App\Command\UserRegisterCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class UserRegisterCommandTest
 *
 * Test cases for the UserRegisterCommand class.
 *
 * @package App\Tests\Command
 */
class UserRegisterCommandTest extends TestCase
{
    /**
     * Test case for empty username argument.
     *
     * @return void
     */
    public function testEmptyUsername(): void
    {
        // mock UserManager
        $userManager = $this->createMock(UserManager::class);

        // create the command with the mocked UserManager.
        $command = new UserRegisterCommand($userManager);

        // create application and add command
        $application = new Application();
        $application->add($command);

        // create CommandTester
        $commandTester = new CommandTester($command);

        // simulate command execution with empty username
        $commandTester->execute([
            'command' => $command->getName(),
            'username' => '',
        ]);

        // get output
        $output = $commandTester->getDisplay();

        // assert error message
        $this->assertStringContainsString('Username cannot be empty.', $output);
    }

    /**
     * Test case for registering a new user successfully.
     *
     * @return void
     */
    public function testRegisterUserSuccess(): void
    {
        // mock UserManager
        $userManager = $this->createMock(UserManager::class);

        // configure the UserManager mock.
        $userManager->expects($this->once())
            ->method('getUserRepo')
            ->with(['username' => 'newuser'])
            ->willReturn(null); // Simulate user not existing

        $userManager->expects($this->once())
            ->method('registerUser')
            ->with('newuser');

        // create the command with the mocked UserManager.
        $command = new UserRegisterCommand($userManager);

        // create application and add command
        $application = new Application();
        $application->add($command);

        // create CommandTester
        $commandTester = new CommandTester($command);

        // simulate command execution with arguments
        $commandTester->execute([
            'command' => $command->getName(),
            'username' => 'newuser',
        ]);

        // get output
        $output = $commandTester->getDisplay();

        // assert output
        $this->assertStringContainsString('New user registered username: newuser', $output);
    }
}
