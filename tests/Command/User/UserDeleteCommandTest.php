<?php

namespace App\Tests\Command\User;

use App\Entity\User;
use App\Manager\UserManager;
use PHPUnit\Framework\TestCase;
use App\Command\User\UserDeleteCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class UserDeleteCommandTest
 *
 * Test the command to delete a user
 *
 * @package App\Tests\Command\User
 */
class UserDeleteCommandTest extends TestCase
{
    /**
     * Test the command to delete a user with not exist user error
     *
     * @return void
     */
    public function testExecuteUserNotExist(): void
    {
        $username = 'nonexistentuser';

        // mock the UserManager
        $userManager = $this->createMock(UserManager::class);
        $userManager->method('checkIfUserExist')->with($username)->willReturn(false);

        // create the command with the mocked UserManager
        $command = new UserDeleteCommand($userManager);

        // set up the application and the command tester
        $application = new Application();
        $application->add($command);
        $commandTester = new CommandTester($application->find('app:user:delete'));

        // execute the command with the username argument
        $commandTester->execute(['username' => $username]);

        // get command output
        $output = $commandTester->getDisplay();

        // assert that the command output is as expected
        $this->assertStringContainsString('Error username: ' . $username . ' not exist!', $output);
        $this->assertEquals(Command::FAILURE, $commandTester->getStatusCode());
    }

    /**
     * Test the command to delete a user with invalid username error
     *
     * @return void
     */
    public function testExecuteInvalidUsername(): void
    {
        $username = 123; // invalid type

        // mock the UserManager
        $userManager = $this->createMock(UserManager::class);

        // create the command with the mocked UserManager
        $command = new UserDeleteCommand($userManager);

        // set up the application and the command tester
        $application = new Application();
        $application->add($command);
        $commandTester = new CommandTester($application->find('app:user:delete'));

        // execute the command with the username argument
        $commandTester->execute(['username' => $username]);

        // get command output
        $output = $commandTester->getDisplay();

        // assert that the command output is as expected
        $this->assertStringContainsString('Invalid username provided.', $output);
        $this->assertEquals(Command::FAILURE, $commandTester->getStatusCode());
    }

    /**
     * Test the command to delete a user with process error
     *
     * @return void
     */
    public function testExecuteProcessError(): void
    {
        $username = 'testuser';
        $userId = 1;

        // mock the User entity
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn($userId);

        // mock the UserManager
        $userManager = $this->createMock(UserManager::class);
        $userManager->method('checkIfUserExist')->with($username)->willReturn(true);
        $userManager->method('getUserRepository')->with(['username' => $username])->willReturn($user);
        $userManager->method('deleteUser')->with($userId)->will($this->throwException(new \Exception('Some error occurred')));

        // create the command with the mocked UserManager
        $command = new UserDeleteCommand($userManager);

        // set up the application and the command tester
        $application = new Application();
        $application->add($command);
        $commandTester = new CommandTester($application->find('app:user:delete'));

        // execute the command with the username argument
        $commandTester->execute(['username' => $username]);

        // assert that the command output is as expected
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Process error: Some error occurred', $output);
        $this->assertEquals(Command::FAILURE, $commandTester->getStatusCode());
    }

    /**
     * Test the command to delete a user with successful deletion
     *
     * @return void
     */
    public function testExecuteSuccessfulDeletion(): void
    {
        $username = 'testuser';
        $userId = 1;

        // mock the User entity
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn($userId);

        // mock the UserManager
        $userManager = $this->createMock(UserManager::class);
        $userManager->method('checkIfUserExist')->with($username)->willReturn(true);
        $userManager->method('getUserRepository')->with(['username' => $username])->willReturn($user);
        $userManager->expects($this->once())->method('deleteUser')->with($userId);

        // create the command with the mocked UserManager
        $command = new UserDeleteCommand($userManager);

        // set up the application and the command tester
        $application = new Application();
        $application->add($command);
        $commandTester = new CommandTester($application->find('app:user:delete'));

        // execute the command with the username argument
        $commandTester->execute(['username' => $username]);

        // get command output
        $output = $commandTester->getDisplay();

        // assert that the command output is as expected
        $this->assertStringContainsString('User: ' . $username . ' has been deleted!', $output);
        $this->assertEquals(Command::SUCCESS, $commandTester->getStatusCode());
    }
}
