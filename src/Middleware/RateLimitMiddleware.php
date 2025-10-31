<?php

namespace App\Middleware;

use App\Manager\AuthManager;
use App\Util\AppUtil;
use App\Util\CacheUtil;
use App\Manager\ErrorManager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Class RateLimitMiddleware
 *
 * Middleware for request rate limiting
 *
 * @package App\Middleware
 */
class RateLimitMiddleware
{
    private AppUtil $appUtil;
    private CacheUtil $cacheUtil;
    private AuthManager $authManager;
    private ErrorManager $errorManager;
    private UrlGeneratorInterface $urlGenerator;

    public function __construct(
        AppUtil $appUtil,
        CacheUtil $cacheUtil,
        AuthManager $authManager,
        ErrorManager $errorManager,
        UrlGeneratorInterface $urlGenerator
    ) {
        $this->appUtil = $appUtil;
        $this->cacheUtil = $cacheUtil;
        $this->authManager = $authManager;
        $this->errorManager = $errorManager;
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * Handle rate limiting
     *
     * @param RequestEvent $event
     */
    public function onKernelRequest(RequestEvent $event): void
    {
        // check if rate limit is enabled
        if ($this->appUtil->getEnvValue('RATE_LIMIT_ENABLED') == 'false') {
            return;
        }

        // check if user is logged in
        if ($this->authManager->isUserLogedin()) {
            return;
        }

        // get request object
        $request = $event->getRequest();

        // exclude api from rate limiting
        if (
            $request->getPathInfo() == $this->urlGenerator->generate('app_api_external_log') ||
            $request->getPathInfo() == $this->urlGenerator->generate('app_api_system_logs') ||
            $request->getPathInfo() == $this->urlGenerator->generate('api_notifications_get_enabled_status') ||
            $request->getPathInfo() == $this->urlGenerator->generate('api_notifications_get_vapid_public_key') ||
            $request->getPathInfo() == $this->urlGenerator->generate('api_notifications_subscriber') ||
            $request->getPathInfo() == $this->urlGenerator->generate('api_notifications_check_push_subscription') ||
            $request->getPathInfo() == $this->urlGenerator->generate('api_system_resources') ||
            $request->getPathInfo() == $this->urlGenerator->generate('api_terminal') ||
            $request->getPathInfo() == $this->urlGenerator->generate('api_terminal_job_start') ||
            $request->getPathInfo() == $this->urlGenerator->generate('api_terminal_job_status') ||
            $request->getPathInfo() == $this->urlGenerator->generate('api_terminal_job_stop') ||
            $request->getPathInfo() == $this->urlGenerator->generate('api_terminal_job_input') ||
            $request->getPathInfo() == $this->urlGenerator->generate('app_file_system_create_save') ||
            $request->getPathInfo() == $this->urlGenerator->generate('app_file_system_create_directory_save') ||
            $request->getPathInfo() == $this->urlGenerator->generate('app_file_system_save') ||
            $request->getPathInfo() == $this->urlGenerator->generate('app_file_system_delete') ||
            $request->getPathInfo() == $this->urlGenerator->generate('app_file_system_move_save') ||
            $request->getPathInfo() == $this->urlGenerator->generate('app_file_system_upload_chunk') ||
            $request->getPathInfo() == $this->urlGenerator->generate('app_file_system_upload_save') ||
            $request->getPathInfo() == $this->urlGenerator->generate('app_file_system_delete') ||
            $request->getPathInfo() == $this->urlGenerator->generate('app_file_system_delete')
        ) {
            return;
        }

        // build key for cache
        $key = 'rate_limit_' . $request->getClientIp();

        // get current value from cache
        $current = $this->cacheUtil->getValue($key)->get();

        if ($current === null) {
            // set current value to 1 and save to cache (for first request)
            $this->cacheUtil->setValue($key, '1', (int) $this->appUtil->getEnvValue('RATE_LIMIT_INTERVAL'));
        } elseif ((int)$current >= (int) $this->appUtil->getEnvValue('RATE_LIMIT_LIMIT')) {
            $this->errorManager->handleError(
                message: 'To many requests!',
                code: Response::HTTP_TOO_MANY_REQUESTS
            );
        } else {
            // increment current value and save to cache
            $this->cacheUtil->setValue($key, (string)((int)$current + 1), (int) $this->appUtil->getEnvValue('RATE_LIMIT_INTERVAL'));
        }
    }
}
