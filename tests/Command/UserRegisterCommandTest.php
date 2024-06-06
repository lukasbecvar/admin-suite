<?php

namespace App\Tests\Command;

use App\Manager\UserManager;
use PHPUnit\Framework\TestCase;
use App\Command\UserRegisterCommand;
use Symfony\Component\String\ByteString;
use Symfony\Component\Console\Tester\CommandTester;

class UserRegisterCommandTest extends TestCase
{
    private string $testName;

    private CommandTester $commandTester;

    protected function setUp(): void
    {
        $this->testName = ByteString::fromRandom(10)->toString();

        $userManagerMock = $this->createMock(UserManager::class);
        $userManagerMock->method('getUserRepo')->willReturn(null);

        $command = new UserRegisterCommand($userManagerMock);
        $this->commandTester = new CommandTester($command);
    }

    public function testExecuteRegisterValid(): void
    {
        // execute the command
        $this->commandTester->execute([
            'username' => $this->testName
        ]);

        // the output of the command in the console
        $output = $this->commandTester->getDisplay();

        // assert that the output is correct
        $this->assertStringContainsString('New user registered username: ' . $this->testName, $output);
    }
}
