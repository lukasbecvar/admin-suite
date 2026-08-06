<?php

namespace App\Middleware;

use App\Util\AppUtil;
use App\Util\ServerUtil;
use Psr\Log\LoggerInterface;
use App\Manager\ErrorManager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * Class SystemCheckMiddleware
 *
 * Middleware for check if website is running on supported operating system
 *
 * @package App\Middleware
 */
class SystemCheckMiddleware
{
    private AppUtil $appUtil;
    private ServerUtil $serverUtil;
    private LoggerInterface $logger;
    private ErrorManager $errorManager;

    public function __construct(
        AppUtil $appUtil,
        ServerUtil $serverUtil,
        LoggerInterface $logger,
        ErrorManager $errorManager
    ) {
        $this->logger = $logger;
        $this->appUtil = $appUtil;
        $this->serverUtil = $serverUtil;
        $this->errorManager = $errorManager;
    }

    /**
     * Check if system is supported
     *
     * @param RequestEvent $event The request event
     *
     * @return void
     */
    public function onKernelRequest(RequestEvent $event): void
    {
        // check if system is linux
        if (!$this->serverUtil->isSystemSupported()) {
            // handle debug mode exception
            if ($this->appUtil->isDevMode()) {
                $this->errorManager->handleError(
                    message: 'This system is not supported!',
                    code: Response::HTTP_NOT_IMPLEMENTED
                );
            } else {
                $this->logger->error('This system is not supported!');
            }

            // return error response
            $content = $this->errorManager->getErrorView(Response::HTTP_NOT_IMPLEMENTED);
            $response = new Response($content, Response::HTTP_NOT_IMPLEMENTED);
            $event->setResponse($response);
        }
    }
}
