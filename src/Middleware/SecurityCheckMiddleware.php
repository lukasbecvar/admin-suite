<?php

namespace App\Middleware;

use App\Util\AppUtil;
use Psr\Log\LoggerInterface;
use App\Manager\ErrorManager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * Class SecurityCheckMiddleware
 *
 * The middleware for checking the security
 *
 * @package App\Middleware
 */
class SecurityCheckMiddleware
{
    private AppUtil $appUtil;
    private LoggerInterface $logger;
    private ErrorManager $errorManager;

    public function __construct(
        AppUtil $appUtil,
        LoggerInterface $logger,
        ErrorManager $errorManager
    ) {
        $this->appUtil = $appUtil;
        $this->logger = $logger;
        $this->errorManager = $errorManager;
    }

    /**
     * Handle the security check (SSL only check)
     *
     * @param RequestEvent $event The request event
     *
     * @return void
     */
    public function onKernelRequest(RequestEvent $event): void
    {
        // check if SSL check enabled
        if ($this->appUtil->isSSLOnly() && !$this->appUtil->isSsl()) {
            // handle debug mode exception
            if ($this->appUtil->isDevMode()) {
                $this->errorManager->handleError(
                    message: 'ssl is required to access this site.',
                    code: Response::HTTP_UPGRADE_REQUIRED
                );
            } else {
                $this->logger->error('ssl is required to access this site.');
            }

            // render the maintenance template
            $content = $this->errorManager->getErrorView(Response::HTTP_UPGRADE_REQUIRED);
            $response = new Response($content, Response::HTTP_UPGRADE_REQUIRED);
            $event->setResponse($response);
        }
    }
}
