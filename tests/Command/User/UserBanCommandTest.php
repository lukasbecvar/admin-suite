<?php

namespace App\Tests\Command\User;

use Exception;
use App\Entity\User;
use App\Manager\BanManager;
use App\Manager\UserManager;
use PHPUnit\Framework\TestCase;
use App\Command\User\UserBanCommand;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class UserBanCommandTest
 *
 * Test cases for execute the ban user command
 *
 * @package App\Tests\Command\User
 */
#[CoversClass(UserBanCommand::class)]
class UserBanCommandTest extends TestCase
{
    private UserBanCommand $command;
    private CommandTester $commandTester;
    private BanManager & MockObject $banManager;
    private UserManager & MockObject $userManager;

    protected function setUp(): void
    {
        // mock dependencies
        $this->banManager = $this->createMock(BanManager::class);
        $this->userManager = $this->createMock(UserManager::class);

        // initialize the command
        $this->command = new UserBanCommand($this->banManager, $this->userManager);
        $this->commandTester = new CommandTester($this->command);
    }

    /**
     * Test execute command with empty username
     *
     * @return void
     */
    public function testExecuteCommandWithEmptyUsername(): void
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
    public function testExecuteCommandWithInvalidUsername(): void
    {
        // execute command with invalid username
        $exitCode = $this->commandTester->execute(['username' => 12345]);

        // get command output
        $output = $this->commandTester->getDisplay();

        // assert result
        $this->assertStringContainsString('Invalid username type provided (must be string)', $output);
        $this->assertEquals(Command::FAILURE, $exitCode);
    }

    /**
     * Test execute command with non-exist user
     *
     * @return void
     */
    public function testExecuteCommandWhenUserNotExist(): void
    {
        // testing username
        $username = 'nonexistentuser';

        // mock check if user exist method
        $this->userManager->method('checkIfUserExist')->with($username)->willReturn(false);

        // execute command
        $exitCode = $this->commandTester->execute(['username' => $username]);

        // get output command output
        $output = $this->commandTester->getDisplay();

        // assert result
        $this->assertStringContainsString('Error username: ' . $username . ' not exist', $output);
        $this->assertEquals(Command::FAILURE, $exitCode);
    }

    /**
     * Test execute command with non-banned user
     *
     * @return void
     */
    public function testExecuteCommandUserNotBanned(): void
    {
        // testing user data
        $username = 'notbanneduser';
        $userId = 2;

        // mock user
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn($userId);

        // mock user manager
        $this->userManager->method('checkIfUserExist')->with($username)->willReturn(true);
        $this->userManager->method('getUserRepository')->with(['username' => $username])->willReturn($user);

        // mock ban manager
        $this->banManager->method('isUserBanned')->with($userId)->willReturn(false);
        $this->banManager->expects($this->once())->method('banUser')->with($userId);

        // execute command
        $exitCode = $this->commandTester->execute(['username' => $username]);

        // get commnad output
        $output = $this->commandTester->getDisplay();

        // assert result
        $this->assertStringContainsString('User: ' . $username . ' is banned successfully', $output);
        $this->assertEquals(Command::SUCCESS, $exitCode);
    }

    /**
     * Test execute command when ban throws exception
     *
     * @return void
     */
    public function testExecuteCommandWhenBanThrowsException(): void
    {
        // testing user data
        $username = 'banneduser';
        $userId = 1;

        // mock user
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn($userId);

        // mock user manager
        $this->userManager->method('checkIfUserExist')->with($username)->willReturn(true);
        $this->userManager->method('getUserRepository')->with(['username' => $username])->willReturn($user);

        // mock ban manager
        $this->banManager->method('isUserBanned')->with($userId)->willReturn(false);
        $this->banManager->method('banUser')->willThrowException(new Exception('Simulated error'));

        // execute command
        $exitCode = $this->commandTester->execute(['username' => $username]);

        // get command output
        $output = $this->commandTester->getDisplay();

        // assert result
        $this->assertStringContainsString('Process error: Simulated error', $output);
        $this->assertEquals(Command::FAILURE, $exitCode);
    }

    /**
     * Test execute command with banned user
     *
     * @return void
     */
    public function testExecuteCommandWhenUserAlreadyBanned(): void
    {
        // testing user data
        $username = 'banneduser';
        $userId = 1;

        // mock user
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn($userId);

        // mock user manager
        $this->userManager->method('checkIfUserExist')->with($username)->willReturn(true);
        $this->userManager->method('getUserRepository')->with(['username' => $username])->willReturn($user);

        // mock ban manager
        $this->banManager->method('isUserBanned')->with($userId)->willReturn(true);
        $this->banManager->expects($this->once())->method('unbanUser')->with($userId);

        // execute command
        $exitCode = $this->commandTester->execute(['username' => $username]);

        // get command output
        $output = $this->commandTester->getDisplay();

        // assert result
        $this->assertStringContainsString('User: ' . $username . ' is unbanned successfully', $output);
        $this->assertEquals(Command::SUCCESS, $exitCode);
    }
}
