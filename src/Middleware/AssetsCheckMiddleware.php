<?php

namespace App\Middleware;

use App\Util\AppUtil;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * Class AssetsCheckMiddleware
 *
 * The middleware for checking assets storage
 *
 * @package App\Middleware
 */
class AssetsCheckMiddleware
{
    private AppUtil $appUtil;
    private LoggerInterface $logger;

    public function __construct(AppUtil $appUtil, LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->appUtil = $appUtil;
    }

    /**
     * Handle the assets check
     *
     * @param RequestEvent $event The request event
     *
     * @return void
     */
    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$this->appUtil->isAssetsExist()) {
            // log error
            $this->logger->error('build resources not found');

            // build the response
            $response = new Response(
                content: 'Error: build resources not found, please contact service administrator & report this bug on email: ' . $_ENV['ADMIN_CONTACT'],
                status: Response::HTTP_INTERNAL_SERVER_ERROR
            );

            // set the response
            $event->setResponse($response);
        }
    }
}
