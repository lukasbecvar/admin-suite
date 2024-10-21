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
 * Test the send notification command class
 *
 * @package App\Tests\Command\Notifications
 */
class SendNotificationCommandTest extends TestCase
{
    private AppUtil & MockObject $appUtil;
    private SendNotificationCommand $command;
    private NotificationsManager & MockObject $notificationsManager;

    protected function setUp(): void
    {
        // mock the dependencies
        $this->notificationsManager = $this->createMock(NotificationsManager::class);
        $this->appUtil = $this->createMock(AppUtil::class);

        // initialize the command
        $this->command = new SendNotificationCommand($this->appUtil, $this->notificationsManager);
    }

    /**
     * Test execute method when message is empty
     *
     * @return void
     */
    public function testExecuteWhenMessageIsEmpty(): void
    {
        // create a CommandTester for the command
        $commandTester = new CommandTester($this->command);
        $commandTester->setInputs(['']); // simulating empty message

        // execute the command
        $commandTester->execute(['message' => '']);

        // assert the output
        $this->assertStringContainsString('Message cannot be empty.', $commandTester->getDisplay());
        $this->assertEquals(Command::FAILURE, $commandTester->getStatusCode());
    }

    /**
     * Test execute method when message is not a string
     *
     * @return void
     */
    public function testExecuteWhenMessageIsNotString(): void
    {
        // create a CommandTester for the command
        $commandTester = new CommandTester($this->command);
        $commandTester->setInputs(['123']); // simulating invalid message type

        // execute the command
        $commandTester->execute(['message' => [123]]); // invalid message type

        // assert the output
        $this->assertStringContainsString('Invalid message provided.', $commandTester->getDisplay());
        $this->assertEquals(Command::FAILURE, $commandTester->getStatusCode());
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
        $commandTester->setInputs(['Test message']); // valid message

        // execute the command
        $commandTester->execute(['message' => 'Test message']); // valid message

        // assert the output
        $this->assertStringContainsString('Push notifiations is disabled', $commandTester->getDisplay());
        $this->assertEquals(Command::FAILURE, $commandTester->getStatusCode());
    }

    /**
     * Test execute method when sending notification
     *
     * @return void
     */
    public function testExecuteWhenSendingNotification(): void
    {
        // mock the return value for PUSH_NOTIFICATIONS_ENABLED
        $this->appUtil->method('getEnvValue')->willReturn('true');

        // mock the sendNotification method
        $this->notificationsManager->expects($this->once())->method('sendNotification')->with(
            $this->equalTo('Admin-suite notification'),
            $this->equalTo('Test message')
        );

        // create a CommandTester for the command
        $commandTester = new CommandTester($this->command);

        // execute the command
        $commandTester->execute(['message' => 'Test message']);

        // assert the output
        $this->assertStringContainsString('Notification sent successfully.', $commandTester->getDisplay());
        $this->assertEquals(Command::SUCCESS, $commandTester->getStatusCode());
    }
}
