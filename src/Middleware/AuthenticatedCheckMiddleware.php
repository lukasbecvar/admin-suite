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
        if (
            $pathInfo !== '/api/external/log' &&
            $pathInfo !== '/login' &&
            $pathInfo !== '/register' &&
            $pathInfo !== '/' &&
            !str_starts_with($pathInfo, '/error') &&
            !preg_match('#^/(_profiler|_wdt)#', $pathInfo)
        ) {
            // check if user is logged in
            if (!$this->authManager->isUserLogedin()) {
                // get login page route route
                $loginUrl = $this->urlGenerator->generate('app_auth_login');

                // redirect to login page
                $event->setResponse(new RedirectResponse($loginUrl));
            }
        }
    }
}
