<?php

namespace App\Tests\Command\User;

use App\Entity\User;
use App\Manager\BanManager;
use App\Manager\UserManager;
use PHPUnit\Framework\TestCase;
use App\Command\User\UserBanCommand;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class UserBanCommandTest
 *
 * Test cases for execute the ban user command
 *
 * @package App\Tests\Command\User
 */
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
     * Test execute command with non-exist user
     *
     * @return void
     */
    public function testExecuteUserNotExist(): void
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
        $this->assertStringContainsString('Error username: nonexistentuser not exist!', $output);
        $this->assertEquals(Command::FAILURE, $exitCode);
    }

    /**
     * Test execute command with banned user
     *
     * @return void
     */
    public function testExecuteUserAlreadyBanned(): void
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
        $this->assertStringContainsString('User: banneduser unbanned', $output);
        $this->assertEquals(Command::SUCCESS, $exitCode);
    }

    /**
     * Test execute command with non-banned user
     *
     * @return void
     */
    public function testExecuteUserNotBanned(): void
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
        $this->assertStringContainsString('User: notbanneduser banned', $output);
        $this->assertEquals(Command::SUCCESS, $exitCode);
    }

    /**
     * Test execute command with empty username
     *
     * @return void
     */
    public function testExecuteWithEmptyUsername(): void
    {
        // execute command
        $exitCode = $this->commandTester->execute(['username' => '']);

        // get command output
        $output = $this->commandTester->getDisplay();

        // assert result
        $this->assertStringContainsString('Username cannot be empty.', $output);
        $this->assertEquals(Command::FAILURE, $exitCode);
    }

    /**
     * Test execute command with invalid username
     *
     * @return void
     */
    public function testExecuteWithInvalidUsername(): void
    {
        // execute command with invalid username
        $exitCode = $this->commandTester->execute(['username' => 12345]);

        // get command output
        $output = $this->commandTester->getDisplay();

        // assert result
        $this->assertStringContainsString('Invalid username provided.', $output);
        $this->assertEquals(Command::FAILURE, $exitCode);
    }
}
