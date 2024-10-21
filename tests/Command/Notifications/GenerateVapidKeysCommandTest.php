<?php

namespace App\Tests\Command\Notifications;

use App\Util\AppUtil;
use PHPUnit\Framework\TestCase;
use App\Manager\NotificationsManager;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use App\Command\Notifications\GenerateVapidKeysCommand;

/**
 * Class GenerateVapidKeysCommandTest
 *
 * Test the generate vapid keys command class
 *
 * @package App\Tests\Command\Notifications
 */
class GenerateVapidKeysCommandTest extends TestCase
{
    private AppUtil & MockObject $appUtil;
    private GenerateVapidKeysCommand $command;
    private NotificationsManager & MockObject $notificationsManager;

    protected function setUp(): void
    {
        // mock the dependencies
        $this->notificationsManager = $this->createMock(NotificationsManager::class);
        $this->appUtil = $this->createMock(AppUtil::class);

        // initialize the command instance
        $this->command = new GenerateVapidKeysCommand($this->appUtil, $this->notificationsManager);
    }

    /**
     * Test execute method when notifications are disabled
     *
     * @return void
     */
    public function testExecuteWhenNotificationsDisabled(): void
    {
        // mock the return value for PUSH_NOTIFICATIONS_ENABLED
        $this->appUtil->method('getEnvValue')->willReturn('false');

        // create a CommandTester for the command
        $commandTester = new CommandTester($this->command);
        $commandTester->setInputs(['no']); // simulating user input for confirmation

        // execute the command
        $commandTester->execute([]);

        // assert the output
        $this->assertStringContainsString('Push notifiations is disabled', $commandTester->getDisplay());
        $this->assertEquals(Command::FAILURE, $commandTester->getStatusCode());
    }

    /**
     * Test execute method when notifications are enabled
     *
     * @return void
     */
    public function testExecuteWhenRegeneratingVapidKeys(): void
    {
        // mock the return value for PUSH_NOTIFICATIONS_ENABLED
        $this->appUtil->method('getEnvValue')->willReturn('true');

        // mock the regenerateVapidKeys method
        $this->notificationsManager->method('regenerateVapidKeys')->willReturn([
            'publicKey' => 'testPublicKey',
            'privateKey' => 'testPrivateKey'
        ]);

        // create a CommandTester for the command
        $commandTester = new CommandTester($this->command);
        $commandTester->setInputs(['yes']); // simulating user input for confirmation

        // execute the command
        $commandTester->execute([]);

        // assert the output
        $this->assertStringContainsString('VAPID keys generated successfully.', $commandTester->getDisplay());
        $this->assertStringContainsString('Public Key: testPublicKey', $commandTester->getDisplay());
        $this->assertStringContainsString('Private Key: testPrivateKey', $commandTester->getDisplay());
        $this->assertEquals(Command::SUCCESS, $commandTester->getStatusCode());
    }

    /**
     * Test execute method when regeneration is cancelled
     *
     * @return void
     */
    public function testExecuteWhenRegenerationCancelled(): void
    {
        // mock the return value for PUSH_NOTIFICATIONS_ENABLED
        $this->appUtil->method('getEnvValue')->willReturn('true');

        // create a CommandTester for the command
        $commandTester = new CommandTester($this->command);
        $commandTester->setInputs(['no']); // simulating user input for confirmation

        // execute the command
        $commandTester->execute([]);

        // assert the output
        $this->assertStringContainsString('VAPID keys regeneration was cancelled.', $commandTester->getDisplay());
        $this->assertEquals(Command::SUCCESS, $commandTester->getStatusCode());
    }
}
