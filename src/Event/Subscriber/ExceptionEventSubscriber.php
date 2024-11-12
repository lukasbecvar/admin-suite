<?php

namespace App\Event\Subscriber;

use App\Util\AppUtil;
use Psr\Log\LoggerInterface;
use App\Manager\ErrorManager;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class ExceptionEventSubscriber
 *
 * Subscriber to handle error exceptions
 *
 * @package App\EventSubscriber
 */
class ExceptionEventSubscriber implements EventSubscriberInterface
{
    private AppUtil $appUtil;
    private LoggerInterface $logger;
    private ErrorManager $errorManager;

    public function __construct(AppUtil $appUtil, LoggerInterface $logger, ErrorManager $errorManager)
    {
        $this->logger = $logger;
        $this->appUtil = $appUtil;
        $this->errorManager = $errorManager;
    }

    /**
     * Returns an array of event names this subscriber wants to listen to
     *
     * @return array<string> The event names to listen to
     */
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'onKernelException'
        ];
    }

    /**
     * Method called when the KernelEvents::EXCEPTION event is dispatched
     *
     * @param ExceptionEvent $event The event object
     *
     * @return void
     */
    public function onKernelException(ExceptionEvent $event): void
    {
        // get the exception
        $exception = $event->getThrowable();

        // get the error message
        $message = $exception->getMessage();

        // handle untrusted host exception
        if (str_contains($message, 'Untrusted Host') || str_contains($message, 'Invalid host')) {
            if (!$this->appUtil->isDevMode()) {
                $content = $this->errorManager->getErrorView('400');
                $response = new Response($content, Response::HTTP_SERVICE_UNAVAILABLE);
                $event->setResponse($response);
                return;
            }
        }

        // define default exception code
        $statusCode = 500;

        // check if the object is valid exception
        if ($exception instanceof HttpException) {
            // get exception status code
            $statusCode = $exception->getStatusCode();
        }

        /** @var array<array<array<array<mixed>>>> $config monolog config */
        $config = $this->appUtil->getYamlConfig('packages/monolog.yaml');

        /** @var array<mixed> $excludedHttpCodes exluded http codes list */
        $excludedHttpCodes = $config['monolog']['handlers']['filtered']['excluded_http_codes'];

        // check if code is excluded from logging
        if (!in_array($statusCode, $excludedHttpCodes)) {
            // log the error message to exception log
            $this->logger->error($message);
        }
    }
}
