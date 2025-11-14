<?php

namespace App\Controller\Api;

use App\Util\XmlUtil;
use DateTimeImmutable;
use DateTimeInterface;
use App\Util\CacheUtil;
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
    private CacheUtil $cacheUtil;
    private LogManager $logManager;
    private ServiceManager $serviceManager;
    private MonitoringManager $monitoringManager;

    public function __construct(
        XmlUtil $xmlUtil,
        CacheUtil $cacheUtil,
        LogManager $logManager,
        ServiceManager $serviceManager,
        MonitoringManager $monitoringManager
    ) {
        $this->xmlUtil = $xmlUtil;
        $this->cacheUtil = $cacheUtil;
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
        $services = $this->mergeStatusWithServiceConfig($statusSnapshot, $servicesConfig);

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
                'last_monitoring_time' => $this->getLastMonitoringTime()
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

    /**
     * Merge monitoring statuses with service configuration
     *
     * @param array<int, array<string, mixed>> $snapshot Monitoring status snapshot
     * @param array<string, array<string, mixed>> $servicesConfig Monitored services configuration
     *
     * @return array<int, array<string, mixed>> The merged array
     */
    private function mergeStatusWithServiceConfig(array $snapshot, array $servicesConfig): array
    {
        $indexedStatuses = [];
        foreach ($snapshot as $entry) {
            $indexedStatuses[$entry['service_name']] = $entry;
        }

        $services = [];

        foreach ($servicesConfig as $serviceName => $config) {
            $entry = $indexedStatuses[$serviceName] ?? null;
            $services[] = $this->buildServiceEntry($serviceName, $config, $entry);
            unset($indexedStatuses[$serviceName]);
        }

        foreach ($indexedStatuses as $serviceName => $entry) {
            $services[] = $this->buildServiceEntry($serviceName, null, $entry);
        }

        return $services;
    }

    /**
     * Build service entry using config and snapshot data
     *
     * @param string $serviceName Service identifier
     * @param array<string, mixed>|null $config Service configuration
     * @param array<string, mixed>|null $status Monitoring snapshot entry
     *
     * @return array<string, mixed> The service entry
     */
    private function buildServiceEntry(string $serviceName, ?array $config, ?array $status): array
    {
        return [
            'service_name' => $serviceName,
            'display_name' => $config['display_name'] ?? $serviceName,
            'type' => $config['type'] ?? 'virtual',
            'monitoring' => $config['monitoring'] ?? null,
            'status' => $status['status'] ?? 'unknown',
            'message' => $status['message'] ?? null,
            'down_time_minutes' => $status['down_time_minutes'] ?? 0,
            'sla_timeframe' => $status['sla_timeframe'] ?? null,
            'current_sla' => $status['current_sla'] ?? null,
            'last_update_time' => $status['last_update_time'] ?? null
        ];
    }

    /**
     * Get last monitoring time from cache
     *
     * @return string|null Last monitoring timestamp in ISO8601 format
     */
    private function getLastMonitoringTime(): ?string
    {
        if (!$this->cacheUtil->isCatched('last-monitoring-time')) {
            return null;
        }

        $item = $this->cacheUtil->getValue('last-monitoring-time');
        $value = $item->get();

        if ($value instanceof DateTimeInterface) {
            return $value->format(DATE_ATOM);
        }

        if (is_string($value)) {
            return $value;
        }

        return null;
    }
}
