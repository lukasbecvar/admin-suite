<?php

namespace App\Controller\Component;

use Exception;
use App\Util\AppUtil;
use App\Util\CacheUtil;
use App\Util\ExportUtil;
use App\Manager\LogManager;
use App\Manager\ErrorManager;
use App\Manager\MetricsManager;
use App\Manager\ServiceManager;
use App\Manager\DatabaseManager;
use App\Entity\MonitoringStatus;
use App\Annotation\Authorization;
use App\Manager\MonitoringManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * Class MonitoringManagerController
 *
 * Controller for monitoring manager component
 *
 * @package App\Controller\Component
 */
class MonitoringManagerController extends AbstractController
{
    private AppUtil $appUtil;
    private CacheUtil $cacheUtil;
    private ExportUtil $exportUtil;
    private LogManager $logManager;
    private ErrorManager $errorManager;
    private MetricsManager $metricsManager;
    private ServiceManager $serviceManager;
    private DatabaseManager $databaseManager;
    private MonitoringManager $monitoringManager;

    public function __construct(
        AppUtil $appUtil,
        CacheUtil $cacheUtil,
        ExportUtil $exportUtil,
        LogManager $logManager,
        ErrorManager $errorManager,
        MetricsManager $metricsManager,
        ServiceManager $serviceManager,
        DatabaseManager $databaseManager,
        MonitoringManager $monitoringManager
    ) {
        $this->appUtil = $appUtil;
        $this->cacheUtil = $cacheUtil;
        $this->exportUtil = $exportUtil;
        $this->logManager = $logManager;
        $this->errorManager = $errorManager;
        $this->metricsManager = $metricsManager;
        $this->serviceManager = $serviceManager;
        $this->databaseManager = $databaseManager;
        $this->monitoringManager = $monitoringManager;
    }

    /**
     * Render monitoring dashboard page
     *
     * @return Response The monitoring dashboard view
     */
    #[Route('/manager/monitoring', methods:['GET'], name: 'app_manager_monitoring')]
    public function monitoring(): Response
    {
        // get services list
        $services = $this->serviceManager->getServicesList();

        // get pagination limit for monitoring logs
        $pageLimit = (int) $this->appUtil->getEnvValue('LIMIT_CONTENT_PER_PAGE');

        try {
            // get monitoring logs
            $monitoringLogs = $this->logManager->getMonitoringLogs($pageLimit);

            // get database info
            $mainDatabaseName = $this->appUtil->getEnvValue('DATABASE_NAME');
            $monitoringStatusTableName = $this->databaseManager->getEntityTableName(MonitoringStatus::class);

            // get last monitoring time
            $lastMonitoringTime = null;
            if ($this->cacheUtil->isCatched('last-monitoring-time')) {
                // get last monitoring time
                $lastMonitoringTime = $this->cacheUtil->getValue('last-monitoring-time');
            }

            // get sla history
            $slaHistory = $this->monitoringManager->getSLAHistory();
        } catch (Exception $e) {
            $this->errorManager->handleError(
                message: 'error to get monitoring dashboard: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        // return monitoring dashboard page view
        return $this->render('component/monitoring-manager/monitoring-dashboard.twig', [
            'services' => $services,
            'slaHistory' => $slaHistory,
            'monitoringLogs' => $monitoringLogs,
            'mainDatabase' => $mainDatabaseName,
            'serviceManager' => $this->serviceManager,
            'lastMonitoringTime' => $lastMonitoringTime,
            'monitoringManager' => $this->monitoringManager,
            'monitoringStatusTable' => $monitoringStatusTableName
        ]);
    }

    /**
     * Render monitoring config page
     *
     * @return Response The services config view
     */
    #[Authorization(authorization: 'ADMIN')]
    #[Route('/manager/monitoring/config', methods:['GET'], name: 'app_manager_monitoring_config')]
    public function monitoringConfig(): Response
    {
        // get services list
        try {
            $services = $this->serviceManager->getServicesList();
        } catch (Exception $e) {
            $this->errorManager->handleError(
                message: 'error to get services list: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        // return monitoring config page view
        return $this->render('component/monitoring-manager/monitoring-config.twig', [
            'services' => $services
        ]);
    }

    /**
     * Show service details
     *
     * @param Request $request The request object
     *
     * @return Response The service details view
     */
    #[Authorization(authorization: 'ADMIN')]
    #[Route('/manager/monitoring/service', methods:['GET'], name: 'app_manager_monitoring_service_detail')]
    public function serviceDetail(Request $request): Response
    {
        // get service name from request parameter
        $serviceName = (string) $request->query->get('service_name');

        try {
            // get time period from request parameter
            $timePeriod = (string) $request->query->get('time_period', 'last_24_hours');

            // get services list
            $services = $this->serviceManager->getServicesList();

            // check if service exists
            if (!isset($services[$serviceName])) {
                $this->errorManager->handleError(
                    message: 'service not found: ' . $serviceName,
                    code: Response::HTTP_NOT_FOUND
                );
            }

            // get service configuration
            $serviceConfig = $services[$serviceName];

            // get service status
            $serviceStatus = null;
            if ($serviceConfig['type'] === 'systemd') {
                $serviceStatus = $this->serviceManager->isServiceRunning($serviceName) ? 'running' : 'not-running';
            } elseif ($serviceConfig['type'] === 'http') {
                $statusCheck = $this->serviceManager->checkWebsiteStatus($serviceConfig['url']);
                $serviceStatus = $statusCheck['isOnline'] ? 'online' : 'offline';
            }

            // get monitoring status from database
            $monitoringStatus = $this->monitoringManager->getMonitoringStatusRepository(['service_name' => $serviceName]);

            // get SLA history for service
            $slaHistory = $this->monitoringManager->getSLAHistory();
            $serviceSlaHistory = [];

            if (isset($slaHistory[$serviceName])) {
                $serviceSlaHistory = $slaHistory[$serviceName];
            }

            // get metrics data if available
            $metricsData = null;
            $hasMetrics = false;

            // check if service has metrics
            if ($serviceConfig['type'] === 'http' && isset($serviceConfig['metrics_monitoring']) && $serviceConfig['metrics_monitoring']['collect_metrics']) {
                try {
                    // get metrics data
                    $metricsData = $this->metricsManager->getServiceMetrics($serviceName, $timePeriod);
                    $hasMetrics = !empty($metricsData) && !empty($metricsData['metrics']);
                } catch (Exception $e) {
                    $this->errorManager->handleError(
                        message: 'error to get metrics data: ' . $e->getMessage(),
                        code: Response::HTTP_INTERNAL_SERVER_ERROR
                    );
                }
            }

            // return service details view
            return $this->render('component/monitoring-manager/monitoring-service-detail.twig', [
                'serviceName' => $serviceName,
                'serviceConfig' => $serviceConfig,
                'serviceStatus' => $serviceStatus,
                'monitoringStatus' => $monitoringStatus,
                'slaHistory' => $serviceSlaHistory,
                'serviceManager' => $this->serviceManager,
                'metricsData' => $metricsData,
                'hasMetrics' => $hasMetrics,
                'timePeriod' => $timePeriod
            ]);
        } catch (Exception $e) {
            $this->errorManager->handleError(
                message: 'error getting service details: ' . $e->getMessage(),
                code: $e->getCode()
            );
        }
    }

    /**
     * Export SLA history to excel file and download it
     *
     * @return Response The excel file download response
     */
    #[Authorization(authorization: 'ADMIN')]
    #[Route('/manager/monitoring/export/slahistory', methods:['GET'], name: 'app_manager_monitoring_export_slahistory')]
    public function exportSLAHistory(): Response
    {
        try {
            // get sla history data
            $dataToExport = $this->monitoringManager->getSLAHistory();

            // return export response (download excel file)
            return $this->exportUtil->exportSLAHistory($dataToExport);
        } catch (Exception $e) {
            $this->errorManager->handleError(
                message: 'error to export SLA history: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
