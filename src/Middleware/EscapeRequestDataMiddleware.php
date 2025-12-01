<?php

namespace App\Middleware;

use JsonException;
use App\Util\SecurityUtil;
use App\Manager\LogManager;
use App\Manager\ConfigManager;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * Class EscapeRequestDataMiddleware
 *
 * Middleware for escape request data (for security)
 *
 * @package App\Service\Middleware
 */
class EscapeRequestDataMiddleware
{
    private LogManager $logManager;
    private SecurityUtil $securityUtil;
    private ConfigManager $configManager;

    /** @var list<string>|null */
    private ?array $excludedRoutesCache = null;

    // config filename
    private const CONFIG_FILENAME = 'escape-request-exclusions.json';

    public function __construct(LogManager $logManager, SecurityUtil $securityUtil, ConfigManager $configManager)
    {
        $this->logManager = $logManager;
        $this->securityUtil = $securityUtil;
        $this->configManager = $configManager;
    }

    /**
     * Handle request data escaping
     *
     * @param RequestEvent $event The request event
     *
     * @return void
     */
    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        $route = $request->attributes->get('_route');

        if (is_string($route) && in_array($route, $this->getExcludedRoutes(), true)) {
            return;
        }

        // merge GET + POST data
        $requestData = $request->query->all() + $request->request->all();

        // escape data
        array_walk_recursive($requestData, function (&$value) {
            $value = $this->securityUtil->escapeString($value);
        });

        // replace original data
        $request->query->replace($requestData);
        $request->request->replace($requestData);
    }

    /**
     * Get excluded routes that should skip escaping
     *
     * @return list<string>
     */
    private function getExcludedRoutes(): array
    {
        if ($this->excludedRoutesCache !== null) {
            return $this->excludedRoutesCache;
        }

        $configContent = $this->configManager->readConfig(self::CONFIG_FILENAME);
        if ($configContent === null) {
            $this->logManager->log(
                name: 'suite-config',
                message: self::CONFIG_FILENAME . ' not found, escaping all routes by default',
                level: LogManager::LEVEL_WARNING
            );
            return $this->excludedRoutesCache = [];
        }

        try {
            $config = json_decode($configContent, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            $this->logManager->log(
                name: 'suite-config-error',
                message: 'error parsing ' . self::CONFIG_FILENAME . ': ' . $exception->getMessage(),
                level: LogManager::LEVEL_CRITICAL
            );
            return $this->excludedRoutesCache = [];
        }

        $routes = [];
        if (isset($config['routes']) && is_array($config['routes'])) {
            foreach ($config['routes'] as $route) {
                if (is_string($route) && $route !== '') {
                    $routes[] = $route;
                }
            }
        }

        return $this->excludedRoutesCache = $routes;
    }
}
