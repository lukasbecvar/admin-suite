<?php

namespace App\Manager;

use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;

/**
 * Class EmailManager
 *
 * The manager for handling emails
 *
 * @package App\Manager
 */
class EmailManager
{
    private LogManager $logManager;
    private MailerInterface $mailer;
    private ErrorManager $errorManager;

    public function __construct(LogManager $logManager, MailerInterface $mailer, ErrorManager $errorManager)
    {
        $this->mailer = $mailer;
        $this->logManager = $logManager;
        $this->errorManager = $errorManager;
    }

    /**
     * Send a default email with a subject and message
     *
     * @param string $recipient The recipient email
     * @param string $subject The email subject
     * @param string $message The email message
     *
     * @return void
     */
    public function sendDefaultEmail(string $recipient, string $subject, string $message): void
    {
        $this->sendEmail($recipient, $subject, [
            'subject' => $subject,
            'message' => $message,
            'time' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Send an email with a template and context data
     *
     * @param string $recipient The recipient email
     * @param string $subject The email subject
     * @param array<mixed> $context The email context
     * @param string $template The email template
     *
     * @throws TransportExceptionInterface If the email sending fails
     *
     * @return void
     */
    public function sendEmail(string $recipient, string $subject, array $context, string $template = 'default'): void
    {
        // check if mailer is enabled
        if ($_ENV['MAILER_ENABLED'] == 'false') {
            return;
        }

        // build email message
        $email = (new TemplatedEmail())
            ->from($_ENV['MAILER_USERNAME'])
            ->to($recipient)
            ->subject($subject)
            ->htmlTemplate('email/' . $template . '.html.twig')
            ->context($context);

        try {
            // send email
            $this->mailer->send($email);

            // log email sending
            $this->logManager->log('email-send', 'Email sent to ' . $recipient . ' with subject: ' . $subject, 3);
        } catch (TransportExceptionInterface $e) {
            $this->errorManager->handleError('Email sending failed: ' . $e->getMessage(), 500);
        }
    }
}
