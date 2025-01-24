<?php

namespace App\Middleware;

use App\Util\AppUtil;
use App\Util\ServerUtil;
use Psr\Log\LoggerInterface;
use App\Manager\ErrorManager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * Class LinuxCheckMiddleware
 *
 * Middleware for check if website is running on linux server
 *
 * @package App\Middleware
 */
class LinuxCheckMiddleware
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
     * Check if system is linux
     *
     * @param RequestEvent $event The request event
     *
     * @return void
     */
    public function onKernelRequest(RequestEvent $event): void
    {
        // check if system is linux
        if (!$this->serverUtil->isSystemLinux()) {
            // handle debug mode exception
            if ($this->appUtil->isDevMode()) {
                $this->errorManager->handleError(
                    message: 'this system is only for linux.',
                    code: Response::HTTP_NOT_IMPLEMENTED
                );
            } else {
                $this->logger->error('this system can only be used on linux');
            }

            // return error response
            $content = $this->errorManager->getErrorView(Response::HTTP_NOT_IMPLEMENTED);
            $response = new Response($content, Response::HTTP_NOT_IMPLEMENTED);
            $event->setResponse($response);
        }
    }
}
