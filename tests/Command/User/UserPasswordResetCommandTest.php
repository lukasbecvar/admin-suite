<?php

namespace App\Tests\Command\User;

use App\Manager\AuthManager;
use App\Manager\UserManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use PHPUnit\Framework\MockObject\MockObject;
use App\Command\User\UserPasswordResetCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class UserPasswordResetCommandTest
 *
 * Unit test for the UserPasswordResetCommand
 *
 * @package App\Tests\Command\User
 */
class UserPasswordResetCommandTest extends TestCase
{
    /** @var AuthManager&MockObject */
    private AuthManager|MockObject $authManager;

    /** @var UserManager&MockObject */
    private UserManager|MockObject $userManagerMock;

    /**
     * Sets up the mock objects before each test
     *
     * @return void
     */
    protected function setUp(): void
    {
        // mock dependencies
        $this->authManager = $this->createMock(AuthManager::class);
        $this->userManagerMock = $this->createMock(UserManager::class);
    }

    /**
     * Tests a successful password reset
     *
     * @return void
     */
    public function testExecuteResetSuccess(): void
    {
        $username = 'testuser';

        // mock user manager
        $this->userManagerMock->expects($this->once())
            ->method('checkIfUserExist')
            ->with($username)
            ->willReturn(true);

        // mock auth manager
        $this->authManager->expects($this->once())
            ->method('resetUserPassword');

        // create command
        $application = new Application();
        $command = new UserPasswordResetCommand($this->authManager, $this->userManagerMock);
        $application->add($command);

        $commandTester = new CommandTester($application->find('app:user:password:reset'));
        $commandTester->execute(['username' => $username]);

        // get output
        $output = $commandTester->getDisplay();

        // assert output
        $this->assertStringContainsString('new password is', $output);
        $this->assertEquals(Command::SUCCESS, $commandTester->getStatusCode());
    }

    /**
     * Tests the case where the username does not exist
     *
     * @return void
     */
    public function testExecuteUserNotExist(): void
    {
        $username = 'nonexistentuser';

        // mock user manager
        $this->userManagerMock->expects($this->once())
            ->method('checkIfUserExist')
            ->with($username)
            ->willReturn(false);

        // create command
        $application = new Application();
        $command = new UserPasswordResetCommand($this->authManager, $this->userManagerMock);
        $application->add($command);

        // execute command
        $commandTester = new CommandTester($application->find('app:user:password:reset'));
        $commandTester->execute(['username' => $username]);

        // get output
        $output = $commandTester->getDisplay();

        // assert output
        $this->assertStringContainsString('Error username: ' . $username . ' does not exist!', $output);
        $this->assertEquals(Command::FAILURE, $commandTester->getStatusCode());
    }

    /**
     * Tests the case where the provided username is invalid (empty in this case)
     *
     * @return void
     */
    public function testExecuteInvalidUsername(): void
    {
        $username = '';

        // create command
        $application = new Application();
        $command = new UserPasswordResetCommand($this->authManager, $this->userManagerMock);
        $application->add($command);

        // execute command
        $commandTester = new CommandTester($application->find('app:user:password:reset'));
        $commandTester->execute(['username' => $username]);

        // get output
        $output = $commandTester->getDisplay();

        // assert output
        $this->assertStringContainsString('Username cannot be empty.', $output);
        $this->assertEquals(Command::FAILURE, $commandTester->getStatusCode());
    }
}
