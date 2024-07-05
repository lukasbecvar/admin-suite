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
 * The middleware for checking the linux system
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
     * Handle the linux system check
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
                    'This system is only for linux.',
                    Response::HTTP_UPGRADE_REQUIRED
                );
            } else {
                $this->logger->error('this system is only for linux');
            }

            // render the maintenance template
            $content = $this->errorManager->getErrorView(Response::HTTP_UPGRADE_REQUIRED);
            $response = new Response($content, Response::HTTP_UPGRADE_REQUIRED);
            $event->setResponse($response);
        }
    }
}
