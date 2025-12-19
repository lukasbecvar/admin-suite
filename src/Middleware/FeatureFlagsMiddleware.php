<?php

namespace App\Middleware;

use App\Util\AppUtil;
use App\Manager\ErrorManager;
use App\Controller\Api\TerminalApiController;
use Symfony\Component\HttpFoundation\Response;
use App\Controller\Component\TerminalController;
use App\Controller\Api\MetricsExportApiController;
use App\Controller\Component\DiagnosticController;
use App\Controller\Component\SystemAuditController;
use App\Controller\Component\TodoManagerController;
use App\Controller\Api\MonitoringExportApiController;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use App\Controller\Component\DatabaseBrowserController;
use App\Controller\Component\MetricsDashboardController;
use App\Controller\Component\FileSystemBrowserController;
use App\Controller\Component\MonitoringManagerController;
use App\Controller\Api\ServiceVisitorTrackingApiController;

/**
 * Class FeatureFlagsMiddleware
 *
 * Middleware for handling feature flags
 *
 * @package App\Middleware
 */
class FeatureFlagsMiddleware
{
    private AppUtil $appUtil;
    private ErrorManager $errorManager;

    public function __construct(AppUtil $appUtil, ErrorManager $errorManager)
    {
        $this->appUtil = $appUtil;
        $this->errorManager = $errorManager;
    }

    /**
     * Disable controller if feature flag is disabled
     *
     * @param ControllerEvent $event The controller event
     *
     * @return void
     */
    public function onKernelController(ControllerEvent $event): void
    {
        // get controller instance
        $controller = $event->getController();

        if (is_array($controller)) {
            $controllerObject = $controller[0];

            // disable monitoring if feature flag is disabled
            if ($controllerObject instanceof MonitoringManagerController || $controllerObject instanceof ServiceVisitorTrackingApiController || $controllerObject instanceof MonitoringExportApiController) {
                if ($this->appUtil->isFeatureFlagDisabled('monitoring')) {
                    $this->errorManager->handleError(
                        message: 'monitoring is disabled',
                        code: Response::HTTP_NOT_FOUND
                    );
                }
            }

            // disable metrics if feature flag is disabled
            if ($controllerObject instanceof MetricsDashboardController || $controllerObject instanceof MetricsExportApiController) {
                if ($this->appUtil->isFeatureFlagDisabled('metrics')) {
                    $this->errorManager->handleError(
                        message: 'metrics is disabled',
                        code: Response::HTTP_NOT_FOUND
                    );
                }
            }

            // disable database manager if feature flag is disabled
            if ($controllerObject instanceof DatabaseBrowserController) {
                if ($this->appUtil->isFeatureFlagDisabled('database-manager')) {
                    $this->errorManager->handleError(
                        message: 'database manager is disabled',
                        code: Response::HTTP_NOT_FOUND
                    );
                }
            }

            // disable file system manager if feature flag is disabled
            if ($controllerObject instanceof FileSystemBrowserController) {
                if ($this->appUtil->isFeatureFlagDisabled('file-system-manager')) {
                    $this->errorManager->handleError(
                        message: 'file system manager is disabled',
                        code: Response::HTTP_NOT_FOUND
                    );
                }
            }

            // disable terminal if feature flag is disabled
            if ($controllerObject instanceof TerminalController || $controllerObject instanceof TerminalApiController) {
                if ($this->appUtil->isFeatureFlagDisabled('terminal')) {
                    $this->errorManager->handleError(
                        message: 'terminal is disabled',
                        code: Response::HTTP_NOT_FOUND
                    );
                }
            }

            // disable diagnostics if feature flag is disabled
            if ($controllerObject instanceof DiagnosticController) {
                if ($this->appUtil->isFeatureFlagDisabled('diagnostics')) {
                    $this->errorManager->handleError(
                        message: 'diagnostics is disabled',
                        code: Response::HTTP_NOT_FOUND
                    );
                }
            }

            // disable system audit if feature flag is disabled
            if ($controllerObject instanceof SystemAuditController) {
                if ($this->appUtil->isFeatureFlagDisabled('system-audit')) {
                    $this->errorManager->handleError(
                        message: 'system audit is disabled',
                        code: Response::HTTP_NOT_FOUND
                    );
                }
            }

            // disable todo manager if feature flag is disabled
            if ($controllerObject instanceof TodoManagerController) {
                if ($this->appUtil->isFeatureFlagDisabled('todo-manager')) {
                    $this->errorManager->handleError(
                        message: 'todo manager is disabled',
                        code: Response::HTTP_NOT_FOUND
                    );
                }
            }
        }
    }
}
