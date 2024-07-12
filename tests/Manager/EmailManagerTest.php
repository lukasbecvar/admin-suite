<?php

namespace App\Tests\Manager;

use App\Manager\LogManager;
use App\Manager\EmailManager;
use App\Manager\ErrorManager;
use PHPUnit\Framework\TestCase;
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
    /** @var LogManager|MockObject */
    private LogManager|MockObject $logManagerMock;

    /** @var MailerInterface|MockObject */
    private MailerInterface|MockObject $mailerMock;

    /** @var ErrorManager|MockObject */
    private ErrorManager|MockObject $errorManagerMock;

    /**
     * Sets up the mock objects before each test
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->logManagerMock = $this->createMock(LogManager::class);
        $this->mailerMock = $this->createMock(MailerInterface::class);
        $this->errorManagerMock = $this->createMock(ErrorManager::class);
    }

    /**
     * Test send email with disabled mailer
     *
     * @return void
     */
    public function testSendEmailWithDisabledMailer(): void
    {
        $recipient = 'recipient@example.com';
        $subject = 'Test Subject';
        $context = [
            'subject' => $subject,
            'message' => 'Test Message',
            'time' => date('Y-m-d H:i:s')
        ];

        $this->logManagerMock->expects($this->never())->method('log');
        $this->mailerMock->expects($this->never())->method('send');

        $_ENV['MAILER_ENABLED'] = 'false';

        $emailManager = new EmailManager($this->logManagerMock, $this->mailerMock, $this->errorManagerMock);

        // call send email method
        $emailManager->sendEmail($recipient, $subject, $context);
    }

    /**
     * Test send email with exception
     *
     * @return void
     */
    public function testSendEmailWithTransportException(): void
    {
        $recipient = 'recipient@example.com';
        $subject = 'Test Subject';
        $context = [
            'subject' => $subject,
            'message' => 'Test Message',
            'time' => date('Y-m-d H:i:s')
        ];

        $this->logManagerMock->expects($this->never())->method('log');
        $this->mailerMock->expects($this->once())
            ->method('send')
            ->willThrowException(
                new \Symfony\Component\Mailer\Exception\TransportException()
            );
        $this->errorManagerMock->expects($this->once())->method('handleError');

        $_ENV['MAILER_ENABLED'] = 'true';

        $emailManager = new EmailManager($this->logManagerMock, $this->mailerMock, $this->errorManagerMock);

        // call send email method
        $emailManager->sendEmail($recipient, $subject, $context);
    }
}
