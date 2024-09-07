<?php

namespace App\Tests\Command;

use App\Manager\AuthManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use App\Command\RegenerateAuthTokensCommand;
use PHPUnit\Framework\MockObject\MockObject;
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
    private CommandTester $commandTester;
    private AuthManager & MockObject $authManager;

    protected function setUp(): void
    {
        // mock AuthManager
        $this->authManager = $this->createMock(AuthManager::class);

        // set up the expected method calls and their return values
        $this->authManager->expects($this->once())
            ->method('regenerateUsersTokens')
            ->willReturn(['status' => true]);

        // create the command
        $command = new RegenerateAuthTokensCommand($this->authManager);

        // create an application and add the command
        $application = new Application();
        $application->add($command);

        // create a command tester
        $command = $application->find('app:auth:tokens:regenerate');
        $this->commandTester = new CommandTester($command);
    }

    /**
     * Test the execute method
     *
     * @return void
     */
    public function testRegenerateAuthTokensCommand(): void
    {
        // execute the command
        $this->commandTester->execute([]);

        // get output
        $output = $this->commandTester->getDisplay();

        // assert the output
        $this->assertStringContainsString('All tokens is regenerated', $output);
    }
}
