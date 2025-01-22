<?php

namespace App\Controller\Component;

use Exception;
use App\Util\AppUtil;
use App\Util\CacheUtil;
use App\Util\ExportUtil;
use App\Manager\LogManager;
use App\Manager\ErrorManager;
use App\Manager\ServiceManager;
use App\Manager\DatabaseManager;
use App\Entity\MonitoringStatus;
use App\Annotation\Authorization;
use App\Manager\MonitoringManager;
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
    private ServiceManager $serviceManager;
    private DatabaseManager $databaseManager;
    private MonitoringManager $monitoringManager;

    public function __construct(
        AppUtil $appUtil,
        CacheUtil $cacheUtil,
        ExportUtil $exportUtil,
        LogManager $logManager,
        ErrorManager $errorManager,
        ServiceManager $serviceManager,
        DatabaseManager $databaseManager,
        MonitoringManager $monitoringManager
    ) {
        $this->appUtil = $appUtil;
        $this->cacheUtil = $cacheUtil;
        $this->exportUtil = $exportUtil;
        $this->logManager = $logManager;
        $this->errorManager = $errorManager;
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
