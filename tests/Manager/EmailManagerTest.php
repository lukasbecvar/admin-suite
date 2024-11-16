<?php

namespace App\Tests\Manager;

use App\Manager\LogManager;
use App\Manager\EmailManager;
use App\Manager\ErrorManager;
use PHPUnit\Framework\TestCase;
use App\Manager\DatabaseManager;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mailer\Exception\TransportException;

/**
 * Class EmailManagerTest
 *
 * Test cases for email manager
 *
 * @package App\Tests\Manager
 */
class EmailManagerTest extends TestCase
{
    private EmailManager $emailManager;
    private LogManager & MockObject $logManagerMock;
    private MailerInterface & MockObject $mailerMock;
    private ErrorManager & MockObject $errorManagerMock;
    private DatabaseManager & MockObject $databaseManager;

    protected function setUp(): void
    {
        // mock dependencies
        $this->logManagerMock = $this->createMock(LogManager::class);
        $this->mailerMock = $this->createMock(MailerInterface::class);
        $this->errorManagerMock = $this->createMock(ErrorManager::class);
        $this->databaseManager = $this->createMock(DatabaseManager::class);

        // create email manager instance
        $this->emailManager = new EmailManager(
            $this->logManagerMock,
            $this->mailerMock,
            $this->errorManagerMock,
            $this->databaseManager
        );
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

        // expect log manager call
        $this->logManagerMock->expects($this->never())->method('log');

        // expect mailer call
        $this->mailerMock->expects($this->never())->method('send');

        // call tested method
        $this->emailManager->sendEmail($recipient, $subject, $context);
    }

    /**
     * Test send email with enabled mailer
     *
     * @return void
     */
    public function testSendEmailWithMailerEnabled(): void
    {
        // simulate mailer enabled
        $_ENV['MAILER_ENABLED'] = 'true';

        // expect handle error not be called
        $this->errorManagerMock->expects($this->never())->method('handleError');

        // expect mailer send call
        $this->mailerMock->expects($this->once())
            ->method('send');

        // call tested method
        $this->emailManager->sendEmail('recipient@example.com', 'Test Subject', ['key' => 'value']);
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

        // expect log manager call
        $this->logManagerMock->expects($this->never())->method('log');

        // mock send method with exception throw
        $this->mailerMock->expects($this->once())->method('send')->willThrowException(
            new TransportException()
        );

        // expect error manager call
        $this->errorManagerMock->expects($this->once())->method('handleError');

        // call tested method
        $this->emailManager->sendEmail($recipient, $subject, $context);
    }
}
