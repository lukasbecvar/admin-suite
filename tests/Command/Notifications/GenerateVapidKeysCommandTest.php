<?php

namespace App\Tests\Command\Notifications;

use Exception;
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
 * Test cases for execute the generate vapid keys command
 *
 * @package App\Tests\Command\Notifications
 */
class GenerateVapidKeysCommandTest extends TestCase
{
    private CommandTester $commandTester;
    private AppUtil & MockObject $appUtil;
    private GenerateVapidKeysCommand $command;
    private NotificationsManager & MockObject $notificationsManager;

    protected function setUp(): void
    {
        // mock the dependencies
        $this->appUtil = $this->createMock(AppUtil::class);
        $this->notificationsManager = $this->createMock(NotificationsManager::class);

        // initialize the command instance
        $this->command = new GenerateVapidKeysCommand($this->appUtil, $this->notificationsManager);
        $this->commandTester = new CommandTester($this->command);
    }

    /**
     * Test execute command when notifications are disabled
     *
     * @return void
     */
    public function testRegenerateVapidKeysWhenNotificationsIsDisabled(): void
    {
        // mock environment value PUSH_NOTIFICATIONS_ENABLED
        $this->appUtil->method('getEnvValue')->willReturn('false');

        // execute command
        $exitCode = $this->commandTester->execute([]);

        // get command output
        $commandOutput = $this->commandTester->getDisplay();

        // assert result
        $this->assertStringContainsString('Push notifiations is disabled', $commandOutput);
        $this->assertSame(Command::FAILURE, $exitCode);
    }

    /**
     * Test execute command when regeneration is cancelled
     *
     * @return void
     */
    public function testegenerateVapidKeysCancelled(): void
    {
        // mock environment value PUSH_NOTIFICATIONS_ENABLED
        $this->appUtil->method('getEnvValue')->willReturn('true');

        // simulate user confirmation input
        $this->commandTester->setInputs(['no']);

        // execute command
        $exitCode = $this->commandTester->execute([]);

        // get command output
        $commandOutput = $this->commandTester->getDisplay();

        // assert result
        $this->assertStringContainsString('VAPID keys regeneration was cancelled', $commandOutput);
        $this->assertSame(Command::SUCCESS, $exitCode);
    }

    /**
     * Test execute command when response is success
     *
     * @return void
     */
    public function testRegenerateVapidKeysWithSuccessResponse(): void
    {
        // mock environment value PUSH_NOTIFICATIONS_ENABLED
        $this->appUtil->method('getEnvValue')->willReturn('true');

        // mock regeneration method
        $this->notificationsManager->method('regenerateVapidKeys')
            ->willReturn(['publicKey' => 'test_public_key', 'privateKey' => 'test_private_key']);

        // simulate user confirmation input
        $this->commandTester->setInputs(['yes']);

        // execute command
        $exitCode = $this->commandTester->execute([]);

        // get command output
        $commandOutput = $this->commandTester->getDisplay();

        // assert result
        $this->assertStringContainsString('VAPID keys generated successfully', $commandOutput);
        $this->assertStringContainsString('Public Key: test_public_key', $commandOutput);
        $this->assertStringContainsString('Private Key: test_private_key', $commandOutput);
        $this->assertSame(Command::SUCCESS, $exitCode);
    }

    /**
     * Test execute command when response is exception
     *
     * @return void
     */
    public function testRegenerateVapidKeysWithException(): void
    {
        // mock environment value PUSH_NOTIFICATIONS_ENABLED
        $this->appUtil->method('getEnvValue')->willReturn('true');

        // mock regenerate method
        $this->notificationsManager->method('regenerateVapidKeys')->willThrowException(new Exception('Simulated error'));

        // simulate user confirmation input
        $this->commandTester->setInputs(['yes']);

        // execute command
        $exitCode = $this->commandTester->execute([]);

        // get command output
        $commandOutput = $this->commandTester->getDisplay();

        // assert result
        $this->assertStringContainsString('Error to generate VAPID keys: Simulated error', $commandOutput);
        $this->assertSame(Command::FAILURE, $exitCode);
    }
}
