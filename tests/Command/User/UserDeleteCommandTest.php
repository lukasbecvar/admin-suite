<?php

namespace App\Tests\Command\User;

use Exception;
use App\Entity\User;
use App\Manager\UserManager;
use PHPUnit\Framework\TestCase;
use App\Command\User\UserDeleteCommand;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class UserDeleteCommandTest
 *
 * Test cases for execute the command to delete a user
 *
 * @package App\Tests\Command\User
 */
class UserDeleteCommandTest extends TestCase
{
    private UserDeleteCommand $command;
    private CommandTester $commandTester;
    private UserManager & MockObject $userManagerMock;

    protected function setUp(): void
    {
        // mock dependencies
        $this->userManagerMock = $this->createMock(UserManager::class);

        // initialize the command
        $this->command = new UserDeleteCommand($this->userManagerMock);
        $this->commandTester = new CommandTester($this->command);
    }

    /**
     * Test execute command with non-exist user
     *
     * @return void
     */
    public function testExecuteUserNonExist(): void
    {
        $username = 'nonexistentuser';

        // mock user manager
        $this->userManagerMock->method('checkIfUserExist')->with($username)->willReturn(false);

        // execute command
        $exitCode = $this->commandTester->execute(['username' => $username]);

        // get command output
        $output = $this->commandTester->getDisplay();

        // assert result
        $this->assertStringContainsString('Error username: ' . $username . ' not exist', $output);
        $this->assertEquals(Command::FAILURE, $exitCode);
    }

    /**
     * Test execute command to delete a user with invalid username error
     *
     * @return void
     */
    public function testExecuteInvalidUsername(): void
    {
        $username = 123; // invalid type

        // execute command
        $exitCode = $this->commandTester->execute(['username' => $username]);

        // get commnad output
        $output = $this->commandTester->getDisplay();

        // assert result
        $this->assertStringContainsString('Invalid username provided', $output);
        $this->assertEquals(Command::FAILURE, $exitCode);
    }

    /**
     * Test execute command to delete a user with process error
     *
     * @return void
     */
    public function testExecuteProcessError(): void
    {
        $username = 'testuser';
        $userId = 1;

        // mock user
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn($userId);

        // mock user manager
        $this->userManagerMock->method('checkIfUserExist')->with($username)->willReturn(true);
        $this->userManagerMock->method('getUserRepository')->with(['username' => $username])->willReturn($user);
        $this->userManagerMock->method('deleteUser')->with($userId)->will($this->throwException(
            new Exception('Some error occurred')
        ));

        // execute command
        $exitCode = $this->commandTester->execute(['username' => $username]);

        // get command output
        $output = $this->commandTester->getDisplay();

        // assert result
        $this->assertStringContainsString('Process error: Some error occurred', $output);
        $this->assertEquals(Command::FAILURE, $exitCode);
    }

    /**
     * Test execute command to delete a user with successful deletion
     *
     * @return void
     */
    public function testExecuteSuccessfulDeletion(): void
    {
        $username = 'testuser';
        $userId = 1;

        // mock user
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn($userId);

        // mock user manager
        $this->userManagerMock->method('checkIfUserExist')->with($username)->willReturn(true);
        $this->userManagerMock->method('getUserRepository')->with(['username' => $username])->willReturn($user);
        $this->userManagerMock->expects($this->once())->method('deleteUser')->with($userId);

        // execute command
        $exitCode = $this->commandTester->execute(['username' => $username]);

        // get command output
        $output = $this->commandTester->getDisplay();

        // assert result
        $this->assertStringContainsString('User: ' . $username . ' has been deleted', $output);
        $this->assertEquals(Command::SUCCESS, $exitCode);
    }
}
