<?php

namespace App\Tests\Command\Notifications;

use App\Util\AppUtil;
use PHPUnit\Framework\TestCase;
use App\Manager\NotificationsManager;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use App\Command\Notifications\SendNotificationCommand;

/**
 * Class SendNotificationCommandTest
 *
 * Test cases for execute the send notification command
 *
 * @package App\Tests\Command\Notifications
 */
class SendNotificationCommandTest extends TestCase
{
    private CommandTester $commandTester;
    private AppUtil & MockObject $appUtil;
    private SendNotificationCommand $command;
    private NotificationsManager & MockObject $notificationsManager;

    protected function setUp(): void
    {
        // mock the dependencies
        $this->appUtil = $this->createMock(AppUtil::class);
        $this->notificationsManager = $this->createMock(NotificationsManager::class);

        // initialize the command
        $this->command = new SendNotificationCommand($this->appUtil, $this->notificationsManager);
        $this->commandTester = new CommandTester($this->command);
    }

    /**
     * Test execute command when message is empty
     *
     * @return void
     */
    public function testExecuteWhenMessageIsEmpty(): void
    {
        // execute the command with empty message
        $exitCode = $this->commandTester->execute(['message' => '']);

        // get command output
        $commandOutput = $this->commandTester->getDisplay();

        // assert result
        $this->assertStringContainsString('Message cannot be empty.', $commandOutput);
        $this->assertEquals(Command::FAILURE, $exitCode);
    }

    /**
     * Test execute command when message is not a string
     *
     * @return void
     */
    public function testExecuteWhenMessageIsNotString(): void
    {
        // execute the command with integer message
        $exitCode = $this->commandTester->execute(['message' => [123]]);

        // get command output
        $commandOutput = $this->commandTester->getDisplay();

        // assert result
        $this->assertStringContainsString('Invalid message provided.', $commandOutput);
        $this->assertEquals(Command::FAILURE, $exitCode);
    }

    /**
     * Test execute command when notifications are disabled
     *
     * @return void
     */
    public function testExecuteWhenNotificationsDisabled(): void
    {
        // mock environment value PUSH_NOTIFICATIONS_ENABLED
        $this->appUtil->method('getEnvValue')->willReturn('false');

        // execute the command with valid notification message
        $exitCode = $this->commandTester->execute(['message' => 'Test message']);

        // get command output
        $commandOutput = $this->commandTester->getDisplay();

        // assert result
        $this->assertStringContainsString('Push notifiations is disabled', $commandOutput);
        $this->assertEquals(Command::FAILURE, $exitCode);
    }

    /**
     * Test execute command when sending notification
     *
     * @return void
     */
    public function testExecuteWhenSendingNotification(): void
    {
        // mock environment value PUSH_NOTIFICATIONS_ENABLED
        $this->appUtil->method('getEnvValue')->willReturn('true');

        // mock the send notification method
        $this->notificationsManager->expects($this->once())->method('sendNotification')->with(
            $this->equalTo('Admin-suite notification'),
            $this->equalTo('Test message')
        );

        // execute the command with valid notification message
        $exitCode = $this->commandTester->execute(['message' => 'Test message']);

        // get command output
        $commandOutput = $this->commandTester->getDisplay();

        // assert result
        $this->assertStringContainsString('Notification sent successfully.', $commandOutput);
        $this->assertEquals(Command::SUCCESS, $exitCode);
    }
}
