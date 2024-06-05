<?php

namespace App\Tests\Manager;

use App\Manager\LogManager;
use App\Manager\EmailManager;
use App\Manager\ErrorManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Mailer\MailerInterface;

/**
 * Class EmailManagerTest
 *
 * Test the email manager
 *
 * @package App\Tests\Manager
 */
class EmailManagerTest extends TestCase
{
    /**
     * Test if the email can be sent
     *
     * @return void
     */
    public function testSendEmailWithDisabledMailer(): void
    {
        // set the email parameters
        $recipient = 'recipient@example.com';
        $subject = 'Test Subject';
        $context = [
            'subject' => $subject,
            'message' => 'Test Message',
            'time' => date('Y-m-d H:i:s')
        ];

        // create the log manager mock
        $logManagerMock = $this->createMock(LogManager::class);
        $logManagerMock->expects($this->never())
            ->method('log');

        // create the mailer mock
        $mailerMock = $this->createMock(MailerInterface::class);
        $mailerMock->expects($this->never())
            ->method('send');

        // create the error manager mock
        $errorManagerMock = $this->createMock(ErrorManager::class);

        // set MAILER_ENABLED to false
        $_ENV['MAILER_ENABLED'] = 'false';

        // create the email manager
        $emailManager = new EmailManager($logManagerMock, $mailerMock, $errorManagerMock);

        // call send the email
        $emailManager->sendEmail($recipient, $subject, $context);
    }

    /**
     * Test if the email can be sent
     *
     * @return void
     */
    public function testSendEmailWithTransportException(): void
    {
        // set the email parameters
        $recipient = 'recipient@example.com';
        $subject = 'Test Subject';
        $context = [
            'subject' => $subject,
            'message' => 'Test Message',
            'time' => date('Y-m-d H:i:s')
        ];

        // create the log manager mock
        $logManagerMock = $this->createMock(LogManager::class);
        $logManagerMock->expects($this->never())
            ->method('log');

        // create the mailer mock
        $mailerMock = $this->createMock(MailerInterface::class);
        $mailerMock->expects($this->once())
            ->method('send')
            ->willThrowException(new \Symfony\Component\Mailer\Exception\TransportException());

        // create the error manager mock
        $errorManagerMock = $this->createMock(ErrorManager::class);
        $errorManagerMock->expects($this->once())
            ->method('handleError');

        // set MAILER_ENABLED to true
        $_ENV['MAILER_ENABLED'] = 'true';

        // create the email manager
        $emailManager = new EmailManager($logManagerMock, $mailerMock, $errorManagerMock);

        // call send the email
        $emailManager->sendEmail($recipient, $subject, $context);
    }
}
