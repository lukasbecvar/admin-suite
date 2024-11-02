<?php

namespace App\Tests\Command\User;

use DateTime;
use App\Entity\User;
use App\Manager\UserManager;
use App\Util\VisitorInfoUtil;
use PHPUnit\Framework\TestCase;
use App\Command\User\UserListCommand;
use Symfony\Component\Console\Application;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class UserListCommandTest
 *
 * Test for UserListCommand
 *
 * @package App\Tests\Command\User
 */
class UserListCommandTest extends TestCase
{
    private CommandTester $commandTester;
    private UserManager & MockObject $userManager;
    private VisitorInfoUtil & MockObject $visitorInfoUtil;

    protected function setUp(): void
    {
        // mock UserManager
        $this->userManager = $this->createMock(UserManager::class);
        $user1 = new User();
        $user1->setUsername('user1');
        $user1->setRole('ROLE_USER');
        $user1->setIpAddress('127.0.0.1');
        $user1->setUserAgent('Mozilla/5.0');
        $user1->setRegisterTime(new DateTime('2023-01-01 12:00:00'));
        $user1->setLastLoginTime(new DateTime('2023-01-02 10:00:00'));

        // simulate returning one user
        $this->userManager->expects($this->once())
            ->method('getAllUsersRepositories')->willReturn([$user1]);

        // mock VisitorInfoUtil
        $this->visitorInfoUtil = $this->createMock(VisitorInfoUtil::class);
        $this->visitorInfoUtil->expects($this->once())
            ->method('getBrowserShortify')
            ->with('Mozilla/5.0')
            ->willReturn('Mozilla');
        $this->visitorInfoUtil->expects($this->once())
            ->method('getOs')
            ->with('Mozilla/5.0')
            ->willReturn('Unknown OS');

        // create the command instance and inject mocks
        $command = new UserListCommand($this->userManager, $this->visitorInfoUtil);

        // set up the application and command tester
        $application = new Application();
        $application->add($command);

        $command = $application->find('app:user:list');
        $this->commandTester = new CommandTester($command);
    }

    /**
     * Test the execution of the UserListCommand
     *
     * @return void
     */
    public function testExecuteUserListCommand(): void
    {
        // execute the command
        $this->commandTester->execute(['command' => 'app:user:list']);

        // get the output
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
    }
}
