<?php

namespace App\Tests\Command\User;

use Exception;
use App\Entity\User;
use App\Manager\UserManager;
use PHPUnit\Framework\TestCase;
use App\Command\User\UserApiAccessCommand;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class UserApiAccessCommandTest
 *
 * @package App\Tests\Command\User
 */
#[CoversClass(UserApiAccessCommand::class)]
class UserApiAccessCommandTest extends TestCase
{
    private CommandTester $commandTester;
    private UserManager & MockObject $userManager;

    protected function setUp(): void
    {
        // mock user manager
        $this->userManager = $this->createMock(UserManager::class);

        // initialize command
        $command = new UserApiAccessCommand($this->userManager);
        $this->commandTester = new CommandTester($command);
    }

    /**
     * Test execute command with empty username
     *
     * @return void
     */
    public function testExecuteFailsWithEmptyUsername(): void
    {
        // execute command
        $exitCode = $this->commandTester->execute([
            'username' => '',
            'status' => 'enable',
        ]);

        // assert result
        $this->assertSame(Command::FAILURE, $exitCode);
        $this->assertStringContainsString('Invalid username parameter', $this->commandTester->getDisplay());
    }

    /**
     * Test execute command with invalid status
     *
     * @return void
     */
    public function testExecuteFailsWithInvalidStatus(): void
    {
        // execute command
        $exitCode = $this->commandTester->execute([
            'username' => 'test',
            'status' => 'foo',
        ]);

        // assert result
        $this->assertSame(Command::FAILURE, $exitCode);
        $this->assertStringContainsString('Invalid status value provided', $this->commandTester->getDisplay());
    }

    /**
     * Test execute command when user not found
     *
     * @return void
     */
    public function testExecuteFailsWhenUserNotFound(): void
    {
        // mock user not found
        $this->userManager->method('getUserByUsername')->willReturn(null);

        // execute command
        $exitCode = $this->commandTester->execute([
            'username' => 'missing',
            'status' => 'enable',
        ]);

        // assert result
        $this->assertSame(Command::FAILURE, $exitCode);
        $this->assertStringContainsString('User "missing" was not found.', $this->commandTester->getDisplay());
    }

    /**
     * Test execute command when status is already set
     *
     * @return void
     */
    public function testExecuteSkipsWhenStatusAlreadySet(): void
    {
        // mock user
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(5);
        $user->method('getAllowApiAccess')->willReturn(true);
        $this->userManager->method('getUserByUsername')->willReturn($user);

        // execute command
        $exitCode = $this->commandTester->execute([
            'username' => 'tester',
            'status' => 'enable',
        ]);

        // assert result
        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('API access is already enabled for user: tester', $this->commandTester->getDisplay());
    }

    /**
     * Test execute command with success response
     *
     * @return void
     */
    public function testExecuteEnablesApiAccessSuccessfully(): void
    {
        // mock user
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(9);
        $user->method('getAllowApiAccess')->willReturn(false);
        $this->userManager->method('getUserByUsername')->willReturn($user);
        $this->userManager->expects($this->once())->method('updateApiAccessStatus')->with(9, true, 'user-manager');

        // execute command
        $exitCode = $this->commandTester->execute([
            'username' => 'tester',
            'status' => 'enable',
        ]);

        // assert result
        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('API access has been enabled for user: tester', $this->commandTester->getDisplay());
    }

    /**
     * Test execute command when manager throws exception
     *
     * @return void
     */
    public function testExecuteHandlesExceptionFromManager(): void
    {
        // mock user
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(7);
        $user->method('getAllowApiAccess')->willReturn(true);
        $this->userManager->method('getUserByUsername')->willReturn($user);

        // mock manager throwing exception
        $this->userManager->method('updateApiAccessStatus')->willThrowException(new Exception('boom'));

        // execute command
        $exitCode = $this->commandTester->execute([
            'username' => 'tester',
            'status' => 'disable',
        ]);

        // assert result
        $this->assertSame(Command::FAILURE, $exitCode);
        $this->assertStringContainsString('Process error: boom', $this->commandTester->getDisplay());
    }
}
