<?php

namespace App\Middleware;

use Exception;
use App\Manager\LogManager;
use App\Manager\AuthManager;
use App\Manager\ConfigManager;
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
    private LogManager $logManager;
    private AuthManager $authManager;
    private ConfigManager $configManager;
    private UrlGeneratorInterface $urlGenerator;

    public function __construct(
        LogManager $logManager,
        AuthManager $authManager,
        ConfigManager $configManager,
        UrlGeneratorInterface $urlGenerator
    ) {
        $this->logManager = $logManager;
        $this->authManager = $authManager;
        $this->configManager = $configManager;
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
     * Check if path is excluded from authentication check
     *
     * @param string $pathInfo
     *
     * @return bool
     */
    private function isExcludedPath(string $pathInfo): bool
    {
        // get security exclusions from config
        $securityExclusionsConfig = $this->configManager->readConfig('security-exclusions.json');
        if ($securityExclusionsConfig === null) {
            $this->logManager->log('suite-config', 'security-exclusions.json not found, auth check running on all paths', LogManager::LEVEL_WARNING);
            return false;
        }

        try {
            $exclusions = json_decode($securityExclusionsConfig, true, 512, JSON_THROW_ON_ERROR);
        } catch (Exception $e) {
            $this->logManager->log('suite-config-error', 'error parsing security-exclusions.json: ' . $e->getMessage(), LogManager::LEVEL_CRITICAL);
            return false;
        }

        $exactPaths = $exclusions['exact_paths'] ?? [];
        $pathPrefixes = $exclusions['path_prefixes'] ?? [];
        $pathPatterns = $exclusions['path_patterns'] ?? [];

        // check for exact path match
        if (in_array($pathInfo, $exactPaths, true)) {
            return true;
        }

        // check for path prefix
        foreach ($pathPrefixes as $prefix) {
            if (str_starts_with($pathInfo, $prefix)) {
                return true;
            }
        }

        // check for path pattern
        foreach ($pathPatterns as $pattern) {
            if (preg_match('#' . $pattern . '#', $pathInfo)) {
                return true;
            }
        }

        return false;
    }
}
