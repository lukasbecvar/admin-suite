<?php

namespace App\Controller\Component;

use App\Util\AppUtil;
use App\Util\CacheUtil;
use App\Util\ExportUtil;
use App\Manager\LogManager;
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
    private ServiceManager $serviceManager;
    private DatabaseManager $databaseManager;
    private MonitoringManager $monitoringManager;

    public function __construct(
        AppUtil $appUtil,
        CacheUtil $cacheUtil,
        ExportUtil $exportUtil,
        LogManager $logManager,
        ServiceManager $serviceManager,
        DatabaseManager $databaseManager,
        MonitoringManager $monitoringManager
    ) {
        $this->appUtil = $appUtil;
        $this->cacheUtil = $cacheUtil;
        $this->exportUtil = $exportUtil;
        $this->logManager = $logManager;
        $this->serviceManager = $serviceManager;
        $this->databaseManager = $databaseManager;
        $this->monitoringManager = $monitoringManager;
    }

    /**
     * Render the monitoring dashboard page
     *
     * @return Response The monitoring dashboard view
     */
    #[Route('/manager/monitoring', methods:['GET'], name: 'app_manager_monitoring')]
    public function monitoring(): Response
    {
        // get services list
        $services = $this->serviceManager->getServicesList();

        // get page limit from config
        $pageLimit = (int) $this->appUtil->getEnvValue('LIMIT_CONTENT_PER_PAGE');

        // get monitoring logs
        $monitoringLogs = $this->logManager->getMonitoringLogs($pageLimit);

        // default last monitoring time
        $lastMonitoringTime = null;

        // get database info
        $mainDatabaseName = $this->appUtil->getEnvValue('DATABASE_NAME');
        $monitoringStatusTableName = $this->databaseManager->getEntityTableName(MonitoringStatus::class);

        // check if last monitoring time is cached
        if ($this->cacheUtil->isCatched('last-monitoring-time')) {
            // get last monitoring time
            $lastMonitoringTime = $this->cacheUtil->getValue('last-monitoring-time');
        }

        // get sla history
        $slaHistory = $this->monitoringManager->getSLAHistory();

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
     * Render the monitoring config page
     *
     * @return Response The monitoring service config view
     */
    #[Authorization(authorization: 'ADMIN')]
    #[Route('/manager/monitoring/config', methods:['GET'], name: 'app_manager_monitoring_config')]
    public function monitoringConfig(): Response
    {
        // get services list
        $services = $this->serviceManager->getServicesList();

        // return monitoring config page view
        return $this->render('component/monitoring-manager/monitoring-config.twig', [
            'services' => $services
        ]);
    }

    /**
     * Export SLA history to excel file
     *
     * @return Response The excel file download response
     */
    #[Authorization(authorization: 'ADMIN')]
    #[Route('/manager/monitoring/export/slahistory', methods:['GET'], name: 'app_manager_monitoring_export_slahistory')]
    public function exportSLAHistory(): Response
    {
        // get sla history data
        $dataToExport = $this->monitoringManager->getSLAHistory();

        // return export response (download excel file)
        return $this->exportUtil->exportSLAHistory($dataToExport);
    }
}
