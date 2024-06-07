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

class UserUpdateRoleCommandTest extends TestCase
{
    /** @var UserManager|MockObject */
    private UserManager|MockObject $userManager;

    protected function setUp(): void
    {
        $this->userManager = $this->createMock(UserManager::class);
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
        // create command tester
        $commandTester = $this->createCommandTester();
        $commandTester->execute(['username' => '', 'role' => 'ADMIN']);

        // get output
        $output = $commandTester->getDisplay();

        // assert output contains
        $this->assertStringContainsString('Username cannot be empty.', $output);
        $this->assertSame(Command::FAILURE, $commandTester->getStatusCode());
    }

    /**
     * Test execute method with empty role.
     *
     * @return void
     */
    public function testExecuteWithEmptyRole(): void
    {
        // create command tester
        $commandTester = $this->createCommandTester();
        $commandTester->execute(['username' => 'testuser', 'role' => '']);

        // get output
        $output = $commandTester->getDisplay();

        // assert output contains
        $this->assertStringContainsString('Role cannot be empty.', $output);
        $this->assertSame(Command::FAILURE, $commandTester->getStatusCode());
    }

    /**
     * Test execute method with invalid role.
     *
     * @return void
     */
    public function testExecuteWithInvalidRole(): void
    {
        // create command tester
        $commandTester = $this->createCommandTester();
        $commandTester->execute(['username' => 'testuser', 'role' => 123]);

        // get output
        $output = $commandTester->getDisplay();

        // assert output contains
        $this->assertStringContainsString('Invalid role provided.', $output);
        $this->assertSame(Command::FAILURE, $commandTester->getStatusCode());
    }

    /**
     * Test execute method with non existing username.
     *
     * @return void
     */
    public function testExecuteWithNonExistingUsername(): void
    {
        $this->userManager->method('getUserRepo')->willReturn(null);

        // create command tester
        $commandTester = $this->createCommandTester();
        $commandTester->execute(['username' => 'testuser', 'role' => 'ADMIN']);

        // get output
        $output = $commandTester->getDisplay();

        // assert output contains
        $this->assertStringContainsString('Error username: testuser does not exist.', $output);
        $this->assertSame(Command::FAILURE, $commandTester->getStatusCode());
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
        $this->userManager->method('getUserRole')->willReturn('ADMIN');

        // create command tester
        $commandTester = $this->createCommandTester();
        $commandTester->execute(['username' => 'testuser', 'role' => 'ADMIN']);

        // get output
        $output = $commandTester->getDisplay();

        // assert output contains
        $this->assertStringContainsString('Error role: ADMIN is already assigned to user: testuser', $output);
        $this->assertSame(Command::FAILURE, $commandTester->getStatusCode());
    }

    /**
     * Test execute method with invalid user id.
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
        $this->userManager->method('getUserRole')->willReturn('USER');

        // create command tester
        $commandTester = $this->createCommandTester();
        $commandTester->execute(['username' => 'testuser', 'role' => 'ADMIN']);

        // get output
        $output = $commandTester->getDisplay();

        // assert output contains
        $this->assertStringContainsString('Role updated successfully.', $output);
        $this->assertSame(Command::SUCCESS, $commandTester->getStatusCode());
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
        $this->userManager->method('getUserRole')->willReturn('USER');
        $this->userManager->method('updateUserRole')->will($this->throwException(new \Exception('Some error')));

        // create command tester
        $commandTester = $this->createCommandTester();
        $commandTester->execute(['username' => 'testuser', 'role' => 'ADMIN']);

        // get output
        $output = $commandTester->getDisplay();

        // assert output contains
        $this->assertStringContainsString('Error updating role: Some error', $output);
        $this->assertSame(Command::FAILURE, $commandTester->getStatusCode());
    }
}
