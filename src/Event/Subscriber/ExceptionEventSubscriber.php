<?php

namespace App\Event\Subscriber;

use App\Util\AppUtil;
use Psr\Log\LoggerInterface;
use App\Controller\ErrorController;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

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
    private ErrorController $errorController;

    public function __construct(AppUtil $appUtil, LoggerInterface $logger, ErrorController $errorController)
    {
        $this->logger = $logger;
        $this->appUtil = $appUtil;
        $this->errorController = $errorController;
    }

    /**
     * Return array of event names subscriber
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
        // get exception
        $exception = $event->getThrowable();

        // get error message
        $message = $exception->getMessage();

        // define default exception code
        $statusCode = 500;

        // check if object is valid exception
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
            // log error message to exception log
            $this->logger->error($message);
        }

        // call error controller to generate response
        $response = $this->errorController->show($exception);
        $event->setResponse($response);
    }
}
