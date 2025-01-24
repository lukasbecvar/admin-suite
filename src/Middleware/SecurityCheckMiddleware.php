<?php

namespace App\Middleware;

use App\Util\AppUtil;
use App\Manager\ErrorManager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * Class SecurityCheckMiddleware
 *
 * Middleware for checking security rules
 *
 * @package App\Middleware
 */
class SecurityCheckMiddleware
{
    private AppUtil $appUtil;
    private ErrorManager $errorManager;

    public function __construct(AppUtil $appUtil, ErrorManager $errorManager)
    {
        $this->appUtil = $appUtil;
        $this->errorManager = $errorManager;
    }

    /**
     * Handle security rules check
     *
     * @param RequestEvent $event The request event
     *
     * @return void
     */
    public function onKernelRequest(RequestEvent $event): void
    {
        // check if SSL only enabled
        if ($this->appUtil->isSSLOnly() && !$this->appUtil->isSsl()) {
            // handle debug mode exception
            if ($this->appUtil->isDevMode()) {
                $this->errorManager->handleError(
                    message: 'ssl is required to access this site.',
                    code: Response::HTTP_UPGRADE_REQUIRED
                );
            }

            // return error response
            $content = $this->errorManager->getErrorView(Response::HTTP_UPGRADE_REQUIRED);
            $response = new Response($content, Response::HTTP_UPGRADE_REQUIRED);
            $event->setResponse($response);
        }
    }
}
