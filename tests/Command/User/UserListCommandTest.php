<?php

namespace App\Tests\Command\User;

use DateTime;
use App\Entity\User;
use App\Manager\UserManager;
use App\Util\VisitorInfoUtil;
use PHPUnit\Framework\TestCase;
use App\Command\User\UserListCommand;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class UserListCommandTest
 *
 * Test cases for execute user list command
 *
 * @package App\Tests\Command\User
 */
#[CoversClass(UserListCommand::class)]
class UserListCommandTest extends TestCase
{
    private UserListCommand $command;
    private CommandTester $commandTester;
    private UserManager & MockObject $userManager;
    private VisitorInfoUtil & MockObject $visitorInfoUtil;

    protected function setUp(): void
    {
        // mock dependencies
        $this->userManager = $this->createMock(UserManager::class);
        $this->visitorInfoUtil = $this->createMock(VisitorInfoUtil::class);

        // initialize the command
        $this->command = new UserListCommand($this->userManager, $this->visitorInfoUtil);
        $this->commandTester = new CommandTester($this->command);
    }

    /**
     * Test execute command when user list is empty
     *
     * @return void
     */
    public function testExecuteCommandWhenUserListIsEmpty(): void
    {
        // mock user manager
        $this->userManager->method('isUsersEmpty')->willReturn(true);

        // execute the command
        $exitCode = $this->commandTester->execute([]);

        // get command output
        $output = $this->commandTester->getDisplay();

        // assert output contains expected data
        $this->assertStringContainsString('User list is empty', $output);
        $this->assertSame(Command::SUCCESS, $exitCode);
    }

    /**
     * Test execute command when response is success
     *
     * @return void
     */
    public function testExecuteCommandUserListCommandSuccess(): void
    {
        // mock user object
        $user1 = new User();
        $user1->setUsername('user1');
        $user1->setRole('ROLE_USER');
        $user1->setIpAddress('127.0.0.1');
        $user1->setUserAgent('Mozilla/5.0');
        $user1->setRegisterTime(new DateTime('2023-01-01 12:00:00'));
        $user1->setLastLoginTime(new DateTime('2023-01-02 10:00:00'));
        $this->userManager->expects($this->once())->method('getAllUsersRepositories')->willReturn([$user1]);

        // expect call visitor info utils
        $this->visitorInfoUtil->expects($this->once())->method('getBrowserShortify')
            ->with('Mozilla/5.0')->willReturn('Mozilla');
        $this->visitorInfoUtil->expects($this->once())->method('getOs')
            ->with('Mozilla/5.0')->willReturn('Unknown OS');

        // execute the command
        $exitCode = $this->commandTester->execute([]);

        // get command output
        $output = $this->commandTester->getDisplay();

        // assert output contains expected data
        $this->assertStringContainsString('Username', $output);
        $this->assertStringContainsString('user1', $output);
        $this->assertStringContainsString('ROLE_USER', $output);
        $this->assertStringContainsString('127.0.0.1', $output);
        $this->assertStringContainsString('Mozilla', $output);
        $this->assertStringContainsString('Unknown OS', $output);
        $this->assertStringContainsString('2023-01-01 12:00:00', $output);
        $this->assertStringContainsString('2023-01-02 10:00:00', $output);
        $this->assertSame(Command::SUCCESS, $exitCode);
    }
}
