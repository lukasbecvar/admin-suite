<?php

namespace App\Tests\Manager;

use App\Manager\LogManager;
use App\Manager\EmailManager;
use App\Manager\ErrorManager;
use PHPUnit\Framework\TestCase;
use App\Manager\DatabaseManager;
use PHPUnit\Framework\MockObject\MockObject;
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
    /** @var LogManager&MockObject */
    private LogManager|MockObject $logManagerMock;

    /** @var MailerInterface&MockObject */
    private MailerInterface|MockObject $mailerMock;

    /** @var ErrorManager&MockObject */
    private ErrorManager|MockObject $errorManagerMock;

    /** @var DatabaseManager&MockObject */
    private DatabaseManager|MockObject $databaseManager;

    /**
     * Sets up the mock objects before each test
     *
     * @return void
     */
    protected function setUp(): void
    {
        // mock dependencies
        $this->logManagerMock = $this->createMock(LogManager::class);
        $this->mailerMock = $this->createMock(MailerInterface::class);
        $this->errorManagerMock = $this->createMock(ErrorManager::class);
        $this->databaseManager = $this->createMock(DatabaseManager::class);
    }

    /**
     * Test send email with disabled mailer
     *
     * @return void
     */
    public function testSendEmailWithDisabledMailer(): void
    {
        // set mailer enabled to false
        $_ENV['MAILER_ENABLED'] = 'false';

        // create test email
        $recipient = 'recipient@example.com';
        $subject = 'Test Subject';
        $context = [
            'subject' => $subject,
            'message' => 'Test Message',
            'time' => date('Y-m-d H:i:s')
        ];

        // mock log manager
        $this->logManagerMock->expects($this->never())->method('log');
        $this->mailerMock->expects($this->never())->method('send');

        // create email manager
        $emailManager = new EmailManager(
            $this->logManagerMock,
            $this->mailerMock,
            $this->errorManagerMock,
            $this->databaseManager
        );

        // call method
        $emailManager->sendEmail($recipient, $subject, $context);
    }

    /**
     * Test send email with exception
     *
     * @return void
     */
    public function testSendEmailWithTransportException(): void
    {
        // set mailer enabled to true
        $_ENV['MAILER_ENABLED'] = 'true';

        // create test email
        $recipient = 'recipient@example.com';
        $subject = 'Test Subject';
        $context = [
            'subject' => $subject,
            'message' => 'Test Message',
            'time' => date('Y-m-d H:i:s')
        ];

        // mock log manager
        $this->logManagerMock->expects($this->never())->method('log');
        $this->mailerMock->expects($this->once())->method('send')->willThrowException(
            new \Symfony\Component\Mailer\Exception\TransportException()
        );

        // mock error manager
        $this->errorManagerMock->expects($this->once())->method('handleError');

        // create email manager
        $emailManager = new EmailManager(
            $this->logManagerMock,
            $this->mailerMock,
            $this->errorManagerMock,
            $this->databaseManager
        );

        // call method
        $emailManager->sendEmail($recipient, $subject, $context);
    }
}
