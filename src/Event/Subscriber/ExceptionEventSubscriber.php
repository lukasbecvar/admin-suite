<?php

namespace App\Event\Subscriber;

use App\Manager\LogManager;
use Psr\Log\LoggerInterface;
use App\Manager\DatabaseManager;
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
    private DatabaseManager $databaseManager;

    /**
     * List of error patterns that exclude from database log
     *
     * @var array<string>
     */
    private array $databaseLogBlockPattern = [
        'log-error:',
        'Unknown database',
        'Base table or view not found',
        'An exception occurred in the driver'
    ];

    /**
     * List of error patterns that exclude from exception log
     *
     * @var array<string>
     */
    private array $exceptionLogBlockPattern = [
        'No route found'
    ];

    public function __construct(
        LogManager $logManager,
        LoggerInterface $logger,
        DatabaseManager $databaseManager
    ) {
        $this->logger = $logger;
        $this->logManager = $logManager;
        $this->databaseManager = $databaseManager;
    }

    /**
     * Get the subscribed events
     *
     * @return array<string> The subscribed events
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'onKernelException'
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

        // check if the event can be logged in database
        if ($this->canBeEventLogged($message)) {
            // log the exception to admin-suite database
            if (!$this->databaseManager->isDatabaseDown()) {
                $this->logManager->log('exception', $message, LogManager::LEVEL_CRITICAL);
            }
        }

        // check if the event can be logged
        if ($this->canBeEventLogged($message, $this->exceptionLogBlockPattern)) {
            // log the error message to exception log
            $this->logger->error($message);
        }
    }

    /**
     * Checks if an event can be logged based on the error message
     *
     * @param string $errorMessage The error message to be checked
     * @param array<string> $blockPatterns The list of patterns that can't be logged
     *
     * @return bool Returns true if the event can be dispatched, otherwise false
     */
    public function canBeEventLogged(string $errorMessage, array $blockPatterns = null): bool
    {
        $blockPatterns = $blockPatterns ?? $this->databaseLogBlockPattern;

        // loop through each blocked error pattern
        foreach ($blockPatterns as $pattern) {
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
