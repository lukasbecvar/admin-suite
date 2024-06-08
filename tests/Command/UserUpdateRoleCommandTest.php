<?php

namespace App\Tests\Command;

use App\Entity\User;
use App\Manager\UserManager;
use PHPUnit\Framework\TestCase;
use App\Command\UserUpdateRoleCommand;
use Symfony\Component\Console\Application;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class UserUpdateRoleCommandTest
 *
 * Test the user update role command.
 *
 * @package App\Tests\Command
 */
class UserUpdateRoleCommandTest extends TestCase
{
    /** @var CommandTester */
    private CommandTester $commandTester;

    /** @var UserManager|MockObject */
    private UserManager|MockObject $userManager;

    protected function setUp(): void
    {
        $this->userManager = $this->createMock(UserManager::class);
        $this->commandTester = $this->createCommandTester();
    }

    /**
     * Create command tester.
     *
     * @return CommandTester
     */
    private function createCommandTester(): CommandTester
    {
        $command = new UserUpdateRoleCommand($this->userManager);
        $application = new Application();
        $application->add($command);
        $command = $application->find('app:user:update:role');

        return new CommandTester($command);
    }

    /**
     * Test execute method with empty username.
     *
     * @return void
     */
    public function testExecuteWithEmptyUsername(): void
    {
        // execute command
        $this->commandTester->execute(['username' => '', 'role' => 'ADMIN']);

        // get output
        $output = $this->commandTester->getDisplay();

        // assert output contains
        $this->assertStringContainsString('Username cannot be empty.', $output);
        $this->assertSame(Command::FAILURE, $this->commandTester->getStatusCode());
    }

    /**
     * Test execute method with empty role.
     *
     * @return void
     */
    public function testExecuteWithEmptyRole(): void
    {
        // execute command
        $this->commandTester->execute(['username' => 'testuser', 'role' => '']);

        // get output
        $output = $this->commandTester->getDisplay();

        // assert output contains
        $this->assertStringContainsString('Role cannot be empty.', $output);
        $this->assertSame(Command::FAILURE, $this->commandTester->getStatusCode());
    }

    /**
     * Test execute method with invalid role.
     *
     * @return void
     */
    public function testExecuteWithInvalidRole(): void
    {
        // execute command
        $this->commandTester->execute(['username' => 'testuser', 'role' => 123]);

        // get output
        $output = $this->commandTester->getDisplay();

        // assert output contains
        $this->assertStringContainsString('Invalid role provided.', $output);
        $this->assertSame(Command::FAILURE, $this->commandTester->getStatusCode());
    }

    /**
     * Test execute method with non existing username.
     *
     * @return void
     */
    public function testExecuteWithNonExistingUsername(): void
    {
        // mock repo
        $this->userManager->method('getUserRepo')->willReturn(null);

        // execute command
        $this->commandTester->execute(['username' => 'testuser', 'role' => 'ADMIN']);

        // get output
        $output = $this->commandTester->getDisplay();

        // assert output contains
        $this->assertStringContainsString('Error username: testuser does not exist.', $output);
        $this->assertSame(Command::FAILURE, $this->commandTester->getStatusCode());
    }

    /**
     * Test execute method with same role.
     *
     * @return void
     */
    public function testExecuteWithSameRole(): void
    {
        // create user mock
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(1);

        // mock methods
        $this->userManager->method('getUserRepo')->willReturn($user);
        $this->userManager->method('getUserRoleById')->willReturn('ADMIN');

        // execute command
        $this->commandTester->execute(['username' => 'testuser', 'role' => 'ADMIN']);

        // get output
        $output = $this->commandTester->getDisplay();

        // assert output contains
        $this->assertStringContainsString('Error role: ADMIN is already assigned to user: testuser', $output);
        $this->assertSame(Command::FAILURE, $this->commandTester->getStatusCode());
    }

    /**
     * Test execute method with exception during role update.
     *
     * @return void
     */
    public function testExecuteWithExceptionDuringRoleUpdate(): void
    {
        // create user mock
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(1);

        // mock methods
        $this->userManager->method('getUserRepo')->willReturn($user);
        $this->userManager->method('getUserRoleById')->willReturn('USER');
        $this->userManager->method('updateUserRole')->will($this->throwException(new \Exception('Some error')));

        // execute command
        $this->commandTester->execute(['username' => 'testuser', 'role' => 'ADMIN']);

        // get output
        $output = $this->commandTester->getDisplay();

        // assert output contains
        $this->assertStringContainsString('Error updating role: Some error', $output);
        $this->assertSame(Command::FAILURE, $this->commandTester->getStatusCode());
    }

    /**
     * Test execute with successful role update.
     *
     * @return void
     */
    public function testExecuteSuccessfulRoleUpdate(): void
    {
        // create user mock
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(1);

        // mock methods
        $this->userManager->method('getUserRepo')->willReturn($user);
        $this->userManager->method('getUserRoleById')->willReturn('USER');

        // execute command
        $this->commandTester->execute(['username' => 'testuser', 'role' => 'ADMIN']);

        // get output
        $output = $this->commandTester->getDisplay();

        // assert output contains
        $this->assertStringContainsString('Role updated successfully.', $output);
        $this->assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
    }
}
