<?php

namespace App\Middleware;

use App\Manager\AuthManager;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Class AuthenticatedCheckMiddleware
 *
 * Middleware for checking authentication before accessing admin routes
 *
 * @package App\Middleware
 */
class AuthenticatedCheckMiddleware
{
    private AuthManager $authManager;
    private UrlGeneratorInterface $urlGenerator;

    public function __construct(AuthManager $authManager, UrlGeneratorInterface $urlGenerator)
    {
        $this->authManager = $authManager;
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * Check if user is logged in
     *
     * @param RequestEvent $event The request event
     *
     * @return void
     */
    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        $pathInfo = $request->getPathInfo();

        // check if route is excluded from authentication check
        if (!$this->isExcludedPath($pathInfo)) {
            $request = $event->getRequest();

            // allow API access via API-KEY header only for /api routes
            if (str_starts_with($pathInfo, '/api') && $request->headers->has('API-KEY')) {
                $apiToken = (string) $request->headers->get('API-KEY');
                if ($apiToken !== '' && $this->authManager->authenticateWithApiKey($apiToken)) {
                    return;
                }
            }

            if (!$this->authManager->isUserLogedin()) {
                $loginUrl = $this->urlGenerator->generate('app_auth_login');
                $event->setResponse(new RedirectResponse($loginUrl));
            }
        }
    }

    /**
     * Determine whether the given path bypasses authentication
     *
     * @param string $pathInfo
     *
     * @return bool
     */
    private function isExcludedPath(string $pathInfo): bool
    {
        return $pathInfo === '/api/monitoring/visitor/tracking'
            || $pathInfo === '/api/external/log'
            || $pathInfo === '/register'
            || $pathInfo === '/login'
            || $pathInfo === '/'
            || str_starts_with($pathInfo, '/error')
            || preg_match('#^/(_profiler|_wdt)#', $pathInfo);
    }
}
