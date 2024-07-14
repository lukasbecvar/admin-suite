<?php

namespace App\Tests\Command;

use App\Manager\AuthManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use App\Command\RegenerateAuthTokensCommand;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class RegenerateAuthTokensCommandTest
 *
 * Test the RegenerateAuthTokensCommand class
 *
 * @package App\Tests\Command
 */
class RegenerateAuthTokensCommandTest extends TestCase
{
    /**
     * Test the execute method
     *
     * @return void
     */
    public function testRegenerateAuthTokensCommand(): void
    {
        // mock AuthManager
        $authManager = $this->createMock(AuthManager::class);

        // set up the expected method calls and their return values
        $authManager->expects($this->once())
            ->method('regenerateUsersTokens')->willReturn(['status' => true]);

        // create the command
        $command = new RegenerateAuthTokensCommand($authManager);

        // create an application and add the command
        $application = new Application();
        $application->add($command);

        // create a command tester
        $commandTester = new CommandTester($command);

        // execute the command
        $commandTester->execute([]);

        // get output
        $output = $commandTester->getDisplay();

        // assert the output
        $this->assertStringContainsString('All tokens is regenerated', $output);
    }
}
