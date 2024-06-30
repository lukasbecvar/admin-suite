<?php

namespace App\Tests\Command\User;

use App\Util\SecurityUtil;
use App\Manager\LogManager;
use App\Manager\UserManager;
use PHPUnit\Framework\TestCase;
use Doctrine\ORM\EntityManagerInterface;
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
    /** @var LogManager|MockObject */
    private LogManager|MockObject $logManagerMock;

    /** @var UserManager|MockObject */
    private UserManager|MockObject $userManagerMock;

    /** @var SecurityUtil|MockObject */
    private SecurityUtil|MockObject $securityUtilMock;

    /** @var EntityManagerInterface|MockObject */
    private EntityManagerInterface|MockObject $entityManagerMock;

    /**
     * Sets up the mock objects before each test
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->logManagerMock = $this->createMock(LogManager::class);
        $this->userManagerMock = $this->createMock(UserManager::class);
        $this->securityUtilMock = $this->createMock(SecurityUtil::class);
        $this->entityManagerMock = $this->createMock(EntityManagerInterface::class);
    }

    /**
     * Tests a successful password reset
     *
     * @return void
     */
    public function testExecuteSuccess(): void
    {
        $username = 'testuser';
        $newPassword = 'newRandomPassword';
        $userRepositoryMock = $this->createMock(\App\Entity\User::class);

        $this->userManagerMock->expects($this->once())
            ->method('checkIfUserExist')
            ->with($username)
            ->willReturn(true);

        $this->userManagerMock->expects($this->once())
            ->method('getUserRepository')
            ->with(['username' => $username])
            ->willReturn($userRepositoryMock);

        $this->securityUtilMock->expects($this->once())
            ->method('generateHash')
            ->with($this->isType('string'))
            ->willReturn($newPassword);

        $this->entityManagerMock->expects($this->once())
            ->method('flush');

        $this->logManagerMock->expects($this->once())
            ->method('log')
            ->with('authenticator', $this->stringContains('password reset with cli command is success'));

        $userRepositoryMock->expects($this->once())
            ->method('setPassword')
            ->with($newPassword);

        $application = new Application();
        $command = new UserPasswordResetCommand($this->logManagerMock, $this->userManagerMock, $this->securityUtilMock, $this->entityManagerMock);
        $application->add($command);

        $commandTester = new CommandTester($application->find('app:user:password:reset'));
        $commandTester->execute(['username' => $username]);

        $output = $commandTester->getDisplay();
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

        $this->userManagerMock->expects($this->once())
            ->method('checkIfUserExist')
            ->with($username)
            ->willReturn(false);

        $application = new Application();
        $command = new UserPasswordResetCommand($this->logManagerMock, $this->userManagerMock, $this->securityUtilMock, $this->entityManagerMock);
        $application->add($command);

        $commandTester = new CommandTester($application->find('app:user:password:reset'));
        $commandTester->execute(['username' => $username]);

        $output = $commandTester->getDisplay();
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

        $application = new Application();
        $command = new UserPasswordResetCommand($this->logManagerMock, $this->userManagerMock, $this->securityUtilMock, $this->entityManagerMock);
        $application->add($command);

        $commandTester = new CommandTester($application->find('app:user:password:reset'));
        $commandTester->execute(['username' => $username]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Username cannot be empty.', $output);
        $this->assertEquals(Command::FAILURE, $commandTester->getStatusCode());
    }
}
