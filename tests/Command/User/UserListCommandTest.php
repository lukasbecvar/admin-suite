<?php

namespace App\Tests\Command\User;

use DateTime;
use App\Entity\User;
use App\Manager\UserManager;
use App\Util\VisitorInfoUtil;
use PHPUnit\Framework\TestCase;
use App\Command\User\UserListCommand;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class UserListCommandTest
 *
 * Test execute user list command
 *
 * @package App\Tests\Command\User
 */
class UserListCommandTest extends TestCase
{
    private UserListCommand $command;
    private CommandTester $commandTester;
    private UserManager & MockObject $userManager;
    private VisitorInfoUtil & MockObject $visitorInfoUtil;

    protected function setUp(): void
    {
        // mock user object
        $user1 = new User();
        $user1->setUsername('user1');
        $user1->setRole('ROLE_USER');
        $user1->setIpAddress('127.0.0.1');
        $user1->setUserAgent('Mozilla/5.0');
        $user1->setRegisterTime(new DateTime('2023-01-01 12:00:00'));
        $user1->setLastLoginTime(new DateTime('2023-01-02 10:00:00'));

        // mock user manager
        $this->userManager = $this->createMock(UserManager::class);

        // simulate returning one user
        $this->userManager->expects($this->once())
            ->method('getAllUsersRepositories')->willReturn([$user1]);

        // mock VisitorInfoUtil
        $this->visitorInfoUtil = $this->createMock(VisitorInfoUtil::class);
        $this->visitorInfoUtil->expects($this->once())->method('getBrowserShortify')
            ->with('Mozilla/5.0')
            ->willReturn('Mozilla');
        $this->visitorInfoUtil->expects($this->once())->method('getOs')
            ->with('Mozilla/5.0')
            ->willReturn('Unknown OS');

        // initialize the command
        $this->command = new UserListCommand($this->userManager, $this->visitorInfoUtil);
        $this->commandTester = new CommandTester($this->command);
    }

    /**
     * Test the execution of the UserListCommand
     *
     * @return void
     */
    public function testExecuteUserListCommand(): void
    {
        // execute the command
        $exitCode = $this->commandTester->execute([]);

        // get command output
        $output = $this->commandTester->getDisplay();

        // assert output contains expected data
        $this->assertSame(Command::SUCCESS, $exitCode);
        $this->assertStringContainsString('Username', $output);
        $this->assertStringContainsString('user1', $output);
        $this->assertStringContainsString('ROLE_USER', $output);
        $this->assertStringContainsString('127.0.0.1', $output);
        $this->assertStringContainsString('Mozilla', $output);
        $this->assertStringContainsString('Unknown OS', $output);
        $this->assertStringContainsString('2023-01-01 12:00:00', $output);
        $this->assertStringContainsString('2023-01-02 10:00:00', $output);
    }
}
