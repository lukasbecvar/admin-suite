<?php

namespace App\Tests\Command\User;

use App\Entity\User;
use App\Manager\UserManager;
use PHPUnit\Framework\TestCase;
use App\Command\User\UserDeleteCommand;
use Symfony\Component\Console\Application;
use PHPUnit\Framework\MockObject\MockObject;
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
    /** @var UserManager|MockObject */
    private UserManager|MockObject $userManagerMock;

    /**
     * Sets up the mock objects before each test
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->userManagerMock = $this->createMock(UserManager::class);
    }

    /**
     * Test the command to delete a user with not exist user error
     *
     * @return void
     */
    public function testExecuteUserNotExist(): void
    {
        $username = 'nonexistentuser';

        $this->userManagerMock->method('checkIfUserExist')->with($username)->willReturn(false);

        $command = new UserDeleteCommand($this->userManagerMock);

        $application = new Application();
        $application->add($command);
        $commandTester = new CommandTester($application->find('app:user:delete'));

        $commandTester->execute(['username' => $username]);

        $output = $commandTester->getDisplay();

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

        $command = new UserDeleteCommand($this->userManagerMock);

        $application = new Application();
        $application->add($command);
        $commandTester = new CommandTester($application->find('app:user:delete'));

        $commandTester->execute(['username' => $username]);

        $output = $commandTester->getDisplay();

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

        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn($userId);

        $this->userManagerMock->method('checkIfUserExist')->with($username)->willReturn(true);
        $this->userManagerMock->method('getUserRepository')->with(['username' => $username])->willReturn($user);
        $this->userManagerMock->method('deleteUser')->with($userId)
            ->will($this->throwException(new \Exception('Some error occurred')));

        $command = new UserDeleteCommand($this->userManagerMock);

        $application = new Application();
        $application->add($command);
        $commandTester = new CommandTester($application->find('app:user:delete'));

        $commandTester->execute(['username' => $username]);

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

        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn($userId);

        $this->userManagerMock->method('checkIfUserExist')->with($username)->willReturn(true);
        $this->userManagerMock->method('getUserRepository')->with(['username' => $username])->willReturn($user);
        $this->userManagerMock->expects($this->once())->method('deleteUser')->with($userId);

        $command = new UserDeleteCommand($this->userManagerMock);

        $application = new Application();
        $application->add($command);
        $commandTester = new CommandTester($application->find('app:user:delete'));

        $commandTester->execute(['username' => $username]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('User: ' . $username . ' has been deleted!', $output);
        $this->assertEquals(Command::SUCCESS, $commandTester->getStatusCode());
    }
}
