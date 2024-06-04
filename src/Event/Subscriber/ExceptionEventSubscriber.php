<?php

namespace App\Event\Subscriber;

use App\Manager\LogManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Class ExceptionEventSubscriber
 *
 * The subscriber for the exception event
 *
 * @package App\Event\Subscriber
 */
class ExceptionEventSubscriber implements EventSubscriberInterface
{
    private LogManager $logManager;
    private LoggerInterface $logger;

    public function __construct(LogManager $logManager, LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->logManager = $logManager;
    }

    /**
     * Get the subscribed events
     *
     * @return array<string> The subscribed events
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'onKernelException',
        ];
    }

    /**
     * Handle the exception event
     *
     * @param ExceptionEvent $event The exception event
     *
     * @return void
     */
    public function onKernelException(ExceptionEvent $event): void
    {
        // get the error caller
        $errorCaler = $event->getThrowable()->getTrace()[0]['function'];

        // get the error message
        $message = $event->getThrowable()->getMessage();

        // check if the error caller is the logger
        if ($errorCaler != 'handleError') {
            return;
        }

        // check if the event can be logged
        if ($this->canBeEventLogged($message)) {
            // log the exception
            $this->logManager->log('exception', $message, 1);
        }

        // log the error message with monolog
        $this->logger->error($message);
    }

    /**
     * Check if an event can be logged
     *
     * @param string $errorMessage The error message
     *
     * @return bool If the event can be logged
     */
    public function canBeEventLogged(string $errorMessage): bool
    {
        // list of error patterns that should block event dispatch
        $blockedErrorPatterns = [
            'log-error:',
            'Unknown database',
            'database connection',
            'Base table or view not found',
            'An exception occurred in the driver'
        ];

        // loop through each blocked error pattern
        foreach ($blockedErrorPatterns as $pattern) {
            // check if the current pattern exists in the error message
            if (strpos($errorMessage, $pattern) !== false) {
                // if a blocked pattern is found, return false
                return false;
            }
        }

        // if no blocked patterns are found, return true
        return true;
    }
}
