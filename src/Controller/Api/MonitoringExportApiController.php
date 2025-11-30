<?php

namespace App\Controller\Api;

use App\Util\XmlUtil;
use DateTimeImmutable;
use App\Util\ExportUtil;
use App\Util\ServerUtil;
use App\Manager\LogManager;
use App\Manager\ServiceManager;
use App\Annotation\Authorization;
use App\Manager\MonitoringManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * Class MonitoringExportApiController
 *
 * Provides monitoring data export for external tools
 *
 * @package App\Controller\Api
 */
class MonitoringExportApiController extends AbstractController
{
    private XmlUtil $xmlUtil;
    private ServerUtil $serverUtil;
    private ExportUtil $exportUtil;
    private LogManager $logManager;
    private ServiceManager $serviceManager;
    private MonitoringManager $monitoringManager;

    public function __construct(
        XmlUtil $xmlUtil,
        ServerUtil $serverUtil,
        ExportUtil $exportUtil,
        LogManager $logManager,
        ServiceManager $serviceManager,
        MonitoringManager $monitoringManager
    ) {
        $this->xmlUtil = $xmlUtil;
        $this->serverUtil = $serverUtil;
        $this->exportUtil = $exportUtil;
        $this->logManager = $logManager;
        $this->serviceManager = $serviceManager;
        $this->monitoringManager = $monitoringManager;
    }

    /**
     * Export monitoring snapshot including services status, monitoring logs and SLA history
     *
     * Query parameters:
     * - format: json (default) or xml
     * - logs_limit: number of log entries to include (default 50, max 200)
     *
     * @param Request $request The current HTTP request
     *
     * @return Response Exported monitoring payload
     */
    #[Authorization(authorization: 'USER')]
    #[Route('/api/monitoring/export', methods: ['GET'], name: 'api_monitoring_export')]
    public function export(Request $request): Response
    {
        $format = strtolower((string) $request->query->get('format', 'json'));
        $logsLimit = (int) $request->query->get('logs_limit', '50');
        $logsLimit = max(1, min(200, $logsLimit));

        // get monitoring data
        $servicesConfig = $this->serviceManager->getServicesList() ?? [];
        $statusSnapshot = $this->monitoringManager->getMonitoringStatusSnapshot();
        $services = $this->exportUtil->mergeStatusWithServiceConfig($statusSnapshot, $servicesConfig);

        // build logs list
        $logs = array_map(
            fn ($log) => [
                'id' => $log->getId(),
                'name' => $log->getName(),
                'message' => $log->getMessage(),
                'level' => $log->getLevel(),
                'status' => $log->getStatus(),
                'time' => $log->getTime() ? $log->getTime()->format(DATE_ATOM) : null,
            ],
            $this->logManager->getMonitoringLogs($logsLimit) ?? []
        );

        // build payload
        $payload = [
            'generated_at' => (new DateTimeImmutable())->format(DATE_ATOM),
            'meta' => [
                'logs_limit' => $logsLimit,
                'services_total' => count($services),
                'last_monitoring_time' => $this->serverUtil->getLastMonitoringTime()
            ],
            'services' => $services,
            'monitoring_logs' => $logs,
            'sla_history' => $this->monitoringManager->getSLAHistory()
        ];

        // XML response
        if ($format === 'xml') {
            $xmlContent = $this->xmlUtil->formatToXml($payload, 'monitoring');
            return new Response($xmlContent, Response::HTTP_OK, ['Content-Type' => 'application/xml']);
        }

        return $this->json($payload, JsonResponse::HTTP_OK);
    }
}
