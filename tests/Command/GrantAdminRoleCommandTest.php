<?php

namespace App\Tests\Command;

use App\Entity\User;
use App\Manager\UserManager;
use PHPUnit\Framework\TestCase;
use App\Command\GrantAdminRoleCommand;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class GrantAdminRoleCommandTest
 *
 * Test cases for the GrantAdminRoleCommand class.
 *
 * @package App\Tests\Command
 */
class GrantAdminRoleCommandTest extends TestCase
{
    /**
     * Test case for empty username argument.
     *
     * @return void
     */
    public function testGrantAdminEmptyUsername(): void
    {
        // mock UserManager
        $userManager = $this->createMock(UserManager::class);

        // create instance of the command with mocked UserManager
        $command = new GrantAdminRoleCommand($userManager);

        // create CommandTester to execute the command
        $commandTester = new CommandTester($command);

        // execute command with empty username
        $commandTester->execute([
            'username' => ''
        ]);

        // assert error message
        $this->assertStringContainsString('You must add the admin username argument!', $commandTester->getDisplay());
    }

    /**
     * Test case for non-existing user username.
     *
     * @return void
     */
    public function testGrantAdminNonExistingUser(): void
    {
        // mock UserManager
        $userManager = $this->createMock(UserManager::class);

        // set up UserManager mock behavior
        $userManager->expects($this->any())->method('getUserRepo')->willReturn(null);

        // create instance of the command with mocked UserManager
        $command = new GrantAdminRoleCommand($userManager);

        // create CommandTester to execute the command
        $commandTester = new CommandTester($command);

        // execute command with non-existing user username
        $commandTester->execute([
            'username' => 'nonexisting_username'
        ]);

        // assert error message
        $this->assertStringContainsString('Error username: nonexisting_username is not registered!', $commandTester->getDisplay());
    }

    /**
     * Test case for already admin user username.
     *
     * @return void
     */
    public function testGrantAlreadyAdmin(): void
    {
        // mock UserManager
        $userManager = $this->createMock(UserManager::class);

        // set up UserManager mock behavior
        $userManager->expects($this->any())->method('getUserRepo')->willReturn(new User());
        $userManager->expects($this->any())->method('isUserAdmin')->willReturn(true);

        // create instance of the command with mocked UserManager
        $command = new GrantAdminRoleCommand($userManager);

        // create CommandTester to execute the command
        $commandTester = new CommandTester($command);

        // execute command with already admin user username
        $commandTester->execute([
            'username' => 'existing'
        ]);

        // assert error message
        $this->assertStringContainsString('User: existing is already admin', $commandTester->getDisplay());
    }

    /**
     * Test case for successful grant of admin role.
     *
     * @return void
     */
    public function testGrantAdminSuccess(): void
    {
        // mock UserManager
        $userManager = $this->createMock(UserManager::class);

        // set up UserManager mock behavior
        $userManager->expects($this->any())->method('getUserRepo')->willReturn(new User());
        $userManager->expects($this->any())->method('isUserAdmin')->willReturn(false);
        $userManager->expects($this->once())->method('addAdminRoleToUser')->with('test_user');

        // create instance of the command with mocked UserManager
        $command = new GrantAdminRoleCommand($userManager);

        // create CommandTester to execute the command
        $commandTester = new CommandTester($command);

        // execute command with valid non-admin user username
        $commandTester->execute([
            'username' => 'test_user'
        ]);

        // assert success message
        $this->assertStringContainsString('admin role granted to: test_user', $commandTester->getDisplay());
    }
}
