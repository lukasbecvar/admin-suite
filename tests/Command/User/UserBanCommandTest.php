<?php

namespace App\Tests\Command\User;

use App\Entity\User;
use App\Manager\BanManager;
use App\Manager\UserManager;
use PHPUnit\Framework\TestCase;
use App\Command\User\UserBanCommand;
use Symfony\Component\Console\Application;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class UserBanCommandTest
 *
 * Test the user ban command
 *
 * @package App\Tests\Command\User
 */
class UserBanCommandTest extends TestCase
{
    private CommandTester $commandTester;
    private BanManager & MockObject $banManager;
    private UserManager & MockObject $userManager;

    protected function setUp(): void
    {
        // mock dependencies
        $this->banManager = $this->createMock(BanManager::class);
        $this->userManager = $this->createMock(UserManager::class);

        $application = new Application();
        $command = new UserBanCommand($this->banManager, $this->userManager);
        $application->add($command);

        // create command tester instance
        $this->commandTester = new CommandTester($application->find('app:user:ban'));
    }

    /**
     * Test execute non exist user
     *
     * @return void
     */
    public function testExecuteUserNotExist(): void
    {
        // testing user name
        $username = 'nonexistentuser';

        // mock methods
        $this->userManager->method('checkIfUserExist')->with($username)->willReturn(false);

        // execute command
        $this->commandTester->execute(['username' => $username]);

        // get output
        $output = $this->commandTester->getDisplay();

        // assert output contains
        $this->assertStringContainsString('Error username: nonexistentuser not exist!', $output);
        $this->assertEquals(Command::FAILURE, $this->commandTester->getStatusCode());
    }

    /**
     * Test execute banned user
     *
     * @return void
     */
    public function testExecuteUserAlreadyBanned(): void
    {
        // testing user data
        $username = 'banneduser';
        $userId = 1;

        // mock methods
        $this->userManager->method('checkIfUserExist')->with($username)->willReturn(true);
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn($userId);
        $this->userManager->method('getUserRepository')->with(['username' => $username])->willReturn($user);
        $this->banManager->method('isUserBanned')->with($userId)->willReturn(true);

        // expected output
        $this->banManager->expects($this->once())->method('unbanUser')->with($userId);

        // execute command
        $this->commandTester->execute(['username' => $username]);

        // get output
        $output = $this->commandTester->getDisplay();

        // assert output contains
        $this->assertStringContainsString('User: banneduser unbanned', $output);
        $this->assertEquals(Command::SUCCESS, $this->commandTester->getStatusCode());
    }

    /**
     * Test execute non banned user
     *
     * @return void
     */
    public function testExecuteUserNotBanned(): void
    {
        // testing user data
        $username = 'notbanneduser';
        $userId = 2;

        // mock methods
        $this->userManager->method('checkIfUserExist')->with($username)->willReturn(true);
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn($userId);
        $this->userManager->method('getUserRepository')->with(['username' => $username])->willReturn($user);
        $this->banManager->method('isUserBanned')->with($userId)->willReturn(false);

        // expected output
        $this->banManager->expects($this->once())->method('banUser')->with($userId);

        // execute command
        $this->commandTester->execute(['username' => $username]);

        // get output
        $output = $this->commandTester->getDisplay();

        // assert output contains
        $this->assertStringContainsString('User: notbanneduser banned', $output);
        $this->assertEquals(Command::SUCCESS, $this->commandTester->getStatusCode());
    }

    /**
     * Test execute with empty username
     *
     * @return void
     */
    public function testExecuteWithEmptyUsername(): void
    {
        // execute command
        $this->commandTester->execute(['username' => '']);

        // get output
        $output = $this->commandTester->getDisplay();

        // assert output contains
        $this->assertStringContainsString('Username cannot be empty.', $output);
        $this->assertEquals(Command::FAILURE, $this->commandTester->getStatusCode());
    }

    /**
     * Test execute with invalid username
     *
     * @return void
     */
    public function testExecuteWithInvalidUsername(): void
    {
        // execute command
        $this->commandTester->execute(['username' => 12345]);

        // get output
        $output = $this->commandTester->getDisplay();

        // assert output contains
        $this->assertStringContainsString('Invalid username provided.', $output);
        $this->assertEquals(Command::FAILURE, $this->commandTester->getStatusCode());
    }
}
