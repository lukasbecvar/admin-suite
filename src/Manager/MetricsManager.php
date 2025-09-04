<?php

namespace App\Manager;

use DateTime;
use Exception;
use App\Util\AppUtil;
use App\Entity\Metric;
use App\Util\CacheUtil;
use App\Util\VisitorInfoUtil;
use App\Entity\ServiceVisitor;
use App\Repository\MetricRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\ServiceVisitorRepository;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class MetricsManager
 *
 * Manager for metrics management
 *
 * @package App\Manager
 */
class MetricsManager
{
    private AppUtil $appUtil;
    private CacheUtil $cacheUtil;
    private LogManager $logManager;
    private ErrorManager $errorManager;
    private ServiceManager $serviceManager;
    private VisitorInfoUtil $visitorInfoUtil;
    private DatabaseManager $databaseManager;
    private MetricRepository $metricRepository;
    private EntityManagerInterface $entityManagerInterface;
    private ServiceVisitorRepository $serviceVisitorRepository;

    public function __construct(
        AppUtil $appUtil,
        CacheUtil $cacheUtil,
        LogManager $logManager,
        ErrorManager $errorManager,
        ServiceManager $serviceManager,
        VisitorInfoUtil $visitorInfoUtil,
        DatabaseManager $databaseManager,
        MetricRepository $metricRepository,
        EntityManagerInterface $entityManagerInterface,
        ServiceVisitorRepository $serviceVisitorRepository
    ) {
        $this->appUtil = $appUtil;
        $this->cacheUtil = $cacheUtil;
        $this->logManager = $logManager;
        $this->errorManager = $errorManager;
        $this->serviceManager = $serviceManager;
        $this->visitorInfoUtil = $visitorInfoUtil;
        $this->databaseManager = $databaseManager;
        $this->metricRepository = $metricRepository;
        $this->entityManagerInterface = $entityManagerInterface;
        $this->serviceVisitorRepository = $serviceVisitorRepository;
    }

    /**
     * Get all services metrics
     *
     * @param string $timePeriod The time period
     *
     * @return array<string,mixed> The metrics data
     */
    public function getAllServicesMetrics(string $timePeriod = 'last_24_hours'): array
    {
        $servicesList = $this->serviceManager->getServicesList();

        // check if services list config data is loaded correctly
        if ($servicesList == null) {
            $this->errorManager->handleError(
                message: 'error to get monitored services config',
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        $metrics = [];
        $sortedMetrics = [];

        // get all services with metrics collection is enabled
        foreach ($servicesList as $serviceName => $serviceConfig) {
            if ($serviceConfig['type'] == 'http' && $serviceConfig['metrics_monitoring']['collect_metrics']) {
                $metrics[$serviceName] = $this->getServiceMetrics($serviceName, $timePeriod);
            }
        }

        // first add host-system if it exists
        if (isset($metrics['host-system'])) {
            $sortedMetrics['host-system'] = $metrics['host-system'];
        }

        // then add any remaining services
        foreach ($metrics as $serviceName => $serviceData) {
            if ($serviceName !== 'host-system') {
                $sortedMetrics[$serviceName] = $serviceData;
            }
        }

        return $sortedMetrics;
    }

    /**
     * Get metrics for specific service
     *
     * @param string $serviceName The service name
     * @param string $timePeriod The time period (default is 'last_24_hours')
     *
     * @return array<mixed> The metrics data
     */
    public function getServiceMetrics(string $serviceName, string $timePeriod = 'last_24_hours'): array
    {
        // get monitored services list
        $servicesList = $this->serviceManager->getServicesList();

        // check if services list config data is loaded correctly
        if ($servicesList == null) {
            $this->errorManager->handleError(
                message: 'error to get monitored services config',
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        // check if service found in monitored services list
        if ($serviceName != 'host-system' && !isset($servicesList[$serviceName])) {
            $this->errorManager->handleError(
                message: 'error service: ' . $serviceName . ' not found in monitored services list',
                code: Response::HTTP_NOT_FOUND
            );
        }

        // check if service is configured to collect metrics
        if ($serviceName != 'host-system' && !$servicesList[$serviceName]['metrics_monitoring']['collect_metrics']) {
            $this->errorManager->handleError(
                message: 'error to get metrics: service is not configured to collect metrics',
                code: Response::HTTP_FORBIDDEN
            );
        }

        $metrics = [];
        $categories = [];

        // get metrics
        $metrics = $this->metricRepository->getMetricsByServiceName($serviceName, $timePeriod);

        // format metrics values to 2 decimal places
        foreach ($metrics as $name => $metricGroup) {
            foreach ($metricGroup as $key => $metric) {
                $metrics[$name][$key]['value'] = round($metric['value'], 2);
            }
        }

        // get categories from first metric group
        if (!empty($metrics)) {
            foreach (reset($metrics) as $metric) {
                $categories[] = $metric['time'];
            }
        }

        // round times in categories array for hour rounding
        if ($this->appUtil->getEnvValue('METRICS_SAVE_INTERVAL') == 60) {
            $categories = $this->appUtil->roundTimesInArray($categories);
        }

        // sort metrics order
        $sortedMetrics = [];
        $desiredOrder = ['cpu_usage', 'ram_usage', 'storage_usage', 'network_usage'];

        // first add metrics in the desired order
        foreach ($desiredOrder as $metricName) {
            if (isset($metrics[$metricName])) {
                $sortedMetrics[$metricName] = $metrics[$metricName];
            }
        }

        // then add any remaining metrics
        foreach ($metrics as $metricName => $metricData) {
            if (!in_array($metricName, $desiredOrder)) {
                $sortedMetrics[$metricName] = $metricData;
            }
        }

        // return metrics data with categories
        return [
            'categories' => $categories,
            'metrics' => $sortedMetrics
        ];
    }

    /**
     * Get raw metrics from cache for specific service
     *
     * @param string $serviceName The service name
     *
     * @return array<mixed> The raw metrics data from cache
     */
    public function getRawMetricsFromCache(string $serviceName = 'host-system'): array
    {
        // get monitored services list
        $servicesList = $this->serviceManager->getServicesList();

        // check if services list config data is loaded correctly
        if ($serviceName != 'host-system' && $servicesList == null) {
            $this->errorManager->handleError(
                message: 'error to get monitored services config',
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        // check if service found in monitored services list
        if ($serviceName != 'host-system' && !isset($servicesList[$serviceName])) {
            $this->errorManager->handleError(
                message: 'error service: ' . $serviceName . ' not found in monitored services list',
                code: Response::HTTP_NOT_FOUND
            );
        }

        // check if service is configured to collect metrics
        if ($serviceName !== 'host-system' && !($servicesList[$serviceName]['metrics_monitoring']['collect_metrics'] ?? false)) {
            $this->errorManager->handleError(
                message: 'error to get metrics: service is not configured to collect metrics',
                code: Response::HTTP_FORBIDDEN
            );
        }

        $metrics = [];
        $categories = [];

        // get all metrics for the service
        if ($serviceName === 'host-system') {
            $metricNames = ['cpu_usage', 'ram_usage', 'storage_usage', 'network_usage'];
        } else {
            $metricNames = [];
        }

        foreach ($metricNames as $metricName) {
            $rawValuesKey = $metricName . '_' . $serviceName . '_raw_values';
            $rawTimesKey = $metricName . '_' . $serviceName . '_raw_times';

            // check if we have raw data in cache
            if ($this->cacheUtil->isCatched($rawValuesKey) && $this->cacheUtil->isCatched($rawTimesKey)) {
                $rawValues = $this->cacheUtil->getValue($rawValuesKey)->get() ?? [];
                $rawTimes = $this->cacheUtil->getValue($rawTimesKey)->get() ?? [];

                // remove seconds from times
                $rawTimes = array_map(function ($time) {
                    return (new DateTime($time))->format('H:i');
                }, $rawTimes);

                // only add if we have actual data
                if (count($rawValues) > 0) {
                    // format data for the chart
                    $formattedValues = [];
                    foreach ($rawValues as $index => $value) {
                        if (isset($rawTimes[$index])) {
                            $formattedValues[] = [
                                'value' => round($value, 2),
                                'time' => $rawTimes[$index]
                            ];

                            // add to categories if not already there
                            if (!in_array($rawTimes[$index], $categories)) {
                                $categories[] = $rawTimes[$index];
                            }
                        }
                    }

                    if (!empty($formattedValues)) {
                        $metrics[$metricName] = $formattedValues;
                    }
                }
            }
        }

        // sort categories chronologically
        sort($categories);

        // sort metrics in the desired order
        $sortedMetrics = [];
        $desiredOrder = ['cpu_usage', 'ram_usage', 'storage_usage', 'network_usage'];

        // first add metrics in the desired order
        foreach ($desiredOrder as $metricName) {
            if (isset($metrics[$metricName])) {
                $sortedMetrics[$metricName] = $metrics[$metricName];
            }
        }

        // then add any remaining metrics
        foreach ($metrics as $metricName => $metricData) {
            if (!in_array($metricName, $desiredOrder)) {
                $sortedMetrics[$metricName] = $metricData;
            }
        }

        // return metrics data with categories
        return [
            'categories' => $categories,
            'metrics' => $sortedMetrics
        ];
    }

    /**
     * Save host system resources usage metrics
     *
     * @param float $cpuUsage The CPU usage
     * @param int $ramUsage The RAM usage
     * @param int $storageUsage The storage usage
     * @param float $networkUsage The network usage
     *
     * @return void
     */
    public function saveUsageMetrics(float $cpuUsage, int $ramUsage, int $storageUsage, float $networkUsage): void
    {
        $this->saveMetricWithCacheSummary('cpu_usage', $cpuUsage, 'host-system');
        $this->saveMetricWithCacheSummary('ram_usage', $ramUsage, 'host-system');
        $this->saveMetricWithCacheSummary('storage_usage', $storageUsage, 'host-system');
        $this->saveMetricWithCacheSummary('network_usage', $networkUsage, 'host-system');
    }

    /**
     * Save metric value
     *
     * @param string $metricName The metric name
     * @param string $value The metric value
     * @param string $serviceName The metric service name
     *
     * @return void
     */
    public function saveMetric(string $metricName, string $value, string $serviceName = 'host-system'): void
    {
        // create metric entity
        $metric = new Metric();
        $metric->setName($metricName)
            ->setValue($value)
            ->setServiceName($serviceName)
            ->setTime(new DateTime());

        // persist metric entity
        $this->entityManagerInterface->persist($metric);

        try {
            // flush metric to database
            $this->entityManagerInterface->flush();
        } catch (Exception $e) {
            $this->errorManager->handleError(
                message: 'error to save metric: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Save metric with cache summary
     *
     * Save metrics to cache storage and calculate summary metrics for save to real database (by metrics save interval)
     *
     * @param string $metricName The metric name
     * @param int|float $value The metric value
     * @param string $serviceName The metric service name
     *
     * @return void
     */
    public function saveMetricWithCacheSummary(string $metricName, int|float $value, string $serviceName = 'host-system'): void
    {
        // get monitoring interval and metrics save interval
        $monitoringInterval = (int) $this->appUtil->getEnvValue('MONITORING_INTERVAL') * 60;
        $metricsSaveInterval = (int) $this->appUtil->getEnvValue('METRICS_SAVE_INTERVAL') * 60;

        // define cache keys
        $lastSaveKey = $metricName . '_' . $serviceName . '_last_save_time';
        $rawValuesKey = $metricName . '_' . $serviceName . '_raw_values';
        $rawTimesKey = $metricName . '_' . $serviceName . '_raw_times';

        // get current raw values and times arrays
        $rawValues = $this->cacheUtil->getValue($rawValuesKey)->get() ?? [];
        $rawTimes = $this->cacheUtil->getValue($rawTimesKey)->get() ?? [];

        // add new raw value and timestamp
        $rawValues[] = $value;
        $rawTimes[] = (new DateTime())->format('H:i:s');

        // limit arrays to values within the metrics save interval
        // maximum number of values is based on monitoring interval
        $maxValues = (int) ceil($metricsSaveInterval / $monitoringInterval);
        if (count($rawValues) > $maxValues) {
            $rawValues = array_slice($rawValues, -$maxValues);
            $rawTimes = array_slice($rawTimes, -$maxValues);
        }

        // calculate average from raw values
        $metricSum = array_sum($rawValues);
        $count = count($rawValues);
        $averageValue = $count > 0 ? round($metricSum / $count, 1) : 0;

        // save raw values to cache with expiration set to metrics save interval
        $this->cacheUtil->setValue($rawValuesKey, $rawValues, $metricsSaveInterval);
        $this->cacheUtil->setValue($rawTimesKey, $rawTimes, $metricsSaveInterval);

        // check if metric can save to real database
        if (!$this->cacheUtil->isCatched($lastSaveKey)) {
            // save average value to database
            $this->saveMetric($metricName, (string) $averageValue, $serviceName);

            // set last save time (disable next save until interval passes)
            $this->cacheUtil->setValue($lastSaveKey, time(), $metricsSaveInterval);
        }
    }

    /**
     * Save service metric to database (save raw metric value without average calculation with save interval limit)
     *
     * @param string $metricName The metric name
     * @param int|float $value The metric value
     * @param string $serviceName The metric service name
     *
     * @return bool True if metric saved, false if save skipped
     */
    public function saveServiceMetric(string $metricName, int|float $value, string $serviceName = 'host-system'): bool
    {
        $metricsSaveInterval = (int) $this->appUtil->getEnvValue('METRICS_SAVE_INTERVAL') * 60;
        $lastSaveKey = $metricName . '_' . $serviceName . '_last_save_time';

        // check if last save time is catched
        if ($this->cacheUtil->isCatched($lastSaveKey)) {
            return false;
        }

        // save metric to database
        $this->saveMetric($metricName, (string) $value, $serviceName);

        // set last save time if not catched (disable next save)
        if (!$this->cacheUtil->isCatched($lastSaveKey)) {
            $this->cacheUtil->setValue($lastSaveKey, time(), $metricsSaveInterval);
        }

        return true;
    }

    /**
     * Delete specifc metric from database based on service name and metric name
     *
     * @param string $metricName The metric name
     * @param string $serviceName The metric service name
     *
     * @return void
     */
    public function deleteMetric(string $metricName, string $serviceName = 'host-system'): void
    {
        // get metric entity
        $metrics = $this->metricRepository->findMetricsByNameAndService($metricName, $serviceName);

        // check if metric found
        if ($metrics == null) {
            $this->errorManager->handleError(
                message: 'error to delete metric: ' . $metricName . ' - ' . $serviceName . ' not found',
                code: Response::HTTP_NOT_FOUND
            );
        }

        // delete all metric entities
        foreach ($metrics as $metricEntity) {
            $this->entityManagerInterface->remove($metricEntity);
        }

        // delete metric data
        try {
            $this->entityManagerInterface->flush();
        } catch (Exception $e) {
            $this->errorManager->handleError(
                message: 'error to delete metric: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        // log delete metric event
        $this->logManager->log(
            name: 'metrics-manager',
            message: 'deleted metric: ' . $metricName . ' - ' . $serviceName,
            level: LogManager::LEVEL_WARNING
        );

        // recalculate metrics database ids
        $this->databaseManager->recalculateTableIds($this->databaseManager->getEntityTableName(Metric::class));
    }

    /**
     * Group metrics by service name, metric name, and month
     *
     * @param array<Metric> $metrics Array of metrics to group
     *
     * @return array<string, array<string, mixed>> Grouped metrics
     */
    public function groupMetricsByMonth(array $metrics): array
    {
        $grouped = [];

        foreach ($metrics as $metric) {
            $serviceName = $metric->getServiceName();
            $metricName = $metric->getName();
            $time = $metric->getTime();

            if ($time === null) {
                continue;
            }

            $monthKey = $time->format('Y-m');
            $groupKey = "{$serviceName}|{$metricName}|{$monthKey}";

            if (!isset($grouped[$groupKey])) {
                $grouped[$groupKey] = [
                    'service_name' => $serviceName,
                    'metric_name' => $metricName,
                    'month' => $monthKey,
                    'values' => [],
                    'count' => 0,
                    'sum' => 0
                ];
            }

            $value = (float) $metric->getValue();
            $grouped[$groupKey]['values'][] = $value;
            $grouped[$groupKey]['sum'] += $value;
            $grouped[$groupKey]['count']++;
        }

        return $grouped;
    }

    /**
     * Perform metrics aggregation (restructure the entire table)
     *
     * @param DateTime $cutoffDate The cutoff date for aggregation
     *
     * @return array<string, int> Result statistics
     */
    public function aggregateOldMetrics(DateTime $cutoffDate): array
    {
        // get old and recent metrics
        $oldMetrics = $this->metricRepository->getOldMetrics($cutoffDate);
        $recentMetrics = $this->metricRepository->getRecentMetrics($cutoffDate);

        if (empty($oldMetrics)) {
            return [
                'deleted' => 0,
                'created' => 0,
                'preserved' => count($recentMetrics),
                'space_saved' => 0
            ];
        }

        // group old metrics by month
        $groupedMetrics = $this->groupMetricsByMonth($oldMetrics);

        // start transaction
        $this->entityManagerInterface->beginTransaction();

        try {
            // clear the entire metrics table
            $qb = $this->entityManagerInterface->createQueryBuilder();
            $qb->delete(Metric::class, 'm')->getQuery()->execute();

            // re-insert recent metrics (preserve detailed data for recent period)
            foreach ($recentMetrics as $recentMetric) {
                $newMetric = new Metric();
                $newMetric->setName($recentMetric['name'])
                    ->setValue($recentMetric['value'])
                    ->setServiceName($recentMetric['service_name'])
                    ->setTime($recentMetric['time']);

                $this->entityManagerInterface->persist($newMetric);
            }

            // insert aggregated metrics for old data (one record per month per service per metric)
            $createdCount = 0;
            foreach ($groupedMetrics as $group) {
                $average = round($group['sum'] / $group['count'], 2);

                // create new aggregated metric with first day of the month as timestamp
                $monthDate = DateTime::createFromFormat('Y-m', $group['month']);
                if ($monthDate === false) {
                    continue;
                }
                $monthDate->setDate((int) $monthDate->format('Y'), (int) $monthDate->format('m'), 1);
                $monthDate->setTime(0, 0, 0);

                $aggregatedMetric = new Metric();
                $aggregatedMetric->setName($group['metric_name'])
                    ->setValue((string) $average)
                    ->setServiceName($group['service_name'])
                    ->setTime($monthDate);

                $this->entityManagerInterface->persist($aggregatedMetric);
                $createdCount++;
            }

            // flush all changes
            $this->entityManagerInterface->flush();
            $this->entityManagerInterface->commit();

            // recalculate record ids
            $this->databaseManager->recalculateTableIds($this->databaseManager->getEntityTableName(Metric::class));

            // log aggregation event
            $this->logManager->log(
                name: 'metrics-aggregation',
                message: 'Aggregated ' . count($oldMetrics) . ' old metrics into ' . $createdCount . ' monthly averages',
                level: LogManager::LEVEL_INFO
            );

            return [
                'deleted' => count($oldMetrics),
                'created' => $createdCount,
                'preserved' => count($recentMetrics),
                'space_saved' => $this->estimateSpaceSavings($oldMetrics, $groupedMetrics)
            ];
        } catch (Exception $e) {
            $this->entityManagerInterface->rollback();
            $this->errorManager->handleError(
                message: 'error during metrics aggregation: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Estimate space savings from aggregation
     *
     * @param array<Metric> $oldMetrics Old metrics
     * @param array<string, array<string, mixed>> $groupedMetrics Grouped metrics
     *
     * @return int Estimated bytes saved
     */
    public function estimateSpaceSavings(array $oldMetrics, array $groupedMetrics): int
    {
        // rough estimate: each metric record is about 100 bytes
        $bytesPerRecord = 100;

        $oldRecordsSize = count($oldMetrics) * $bytesPerRecord;
        $newRecordsSize = count($groupedMetrics) * $bytesPerRecord;

        return max(0, $oldRecordsSize - $newRecordsSize);
    }

    /**
     * Format aggregated metrics for display
     *
     * @param array<string, array<string, mixed>> $groupedMetrics Grouped metrics
     * @param int $limit Maximum number of rows to return
     *
     * @return array<int, array<int, mixed>> Formatted table data
     */
    public function formatAggregatedMetricsForDisplay(array $groupedMetrics, int $limit = 20): array
    {
        $tableData = [];

        foreach ($groupedMetrics as $group) {
            $average = round($group['sum'] / $group['count'], 2);
            $tableData[] = [
                $group['service_name'],
                $group['metric_name'],
                $group['month'],
                $group['count'],
                $average
            ];
        }

        return array_slice($tableData, 0, $limit);
    }

    /**
     * Get aggregation preview data
     *
     * @param DateTime $cutoffDate The cutoff date
     *
     * @return array<string, mixed> Preview data
     */
    public function getAggregationPreview(DateTime $cutoffDate): array
    {
        $oldMetrics = $this->metricRepository->getOldMetrics($cutoffDate);
        $recentMetrics = $this->metricRepository->getRecentMetrics($cutoffDate);
        $groupedMetrics = $this->groupMetricsByMonth($oldMetrics);

        return [
            'old_metrics' => $oldMetrics,
            'recent_metrics' => $recentMetrics,
            'grouped_metrics' => $groupedMetrics,
            'space_saved' => $this->estimateSpaceSavings($oldMetrics, $groupedMetrics)
        ];
    }

    /**
     * Register new service visitor
     *
     * @param string $serviceName The service name
     * @param string $ipAddress The ip address
     * @param string $location The location
     * @param string $referer The referer
     * @param string $userAgent The user agent
     *
     * @return void
     */
    public function registerServiceVisitor(string $serviceName, string $ipAddress, string $location, string $referer, string $userAgent): void
    {
        // check if service visitor found
        if ($this->checkIfVisitorAlreadyRegistered($ipAddress, $serviceName)) {
            $this->errorManager->handleError(
                message: 'error to register service visitor: service visitor already exists',
                code: Response::HTTP_NOT_FOUND
            );
        }

        // create service visitor entity
        $serviceVisitor = new ServiceVisitor();
        $serviceVisitor->setServiceName($serviceName)
            ->setLastVisitTime(new DateTime())
            ->setIpAddress($ipAddress)
            ->setLocation($location)
            ->setReferer($referer)
            ->setUserAgent($userAgent);

        // persist service visitor entity
        $this->entityManagerInterface->persist($serviceVisitor);

        try {
            // flush service visitor to database
            $this->entityManagerInterface->flush();
        } catch (Exception $e) {
            $this->errorManager->handleError(
                message: 'error to register service visitor: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Check if visitor is already registered
     *
     * @param string $ipAddress The ip address
     * @param string $serviceName The service name
     *
     * @return bool True if visitor is already registered, false if not
     */
    public function checkIfVisitorAlreadyRegistered(string $ipAddress, string $serviceName): bool
    {
        // get service visitor
        $serviceVisitor = $this->serviceVisitorRepository->findOneBy([
            'ip_address' => $ipAddress,
            'service_name' => $serviceName
        ]);

        // check if service visitor found
        if ($serviceVisitor != null) {
            return true;
        }

        return false;
    }

    /**
     * Get service visitors by service name
     *
     * @param string $serviceName The service name
     * @param int $page The page number
     *
     * @return array<string, mixed> The service visitors data
     */
    public function getVisitorsByServiceName(string $serviceName, int $page = 1): array
    {
        $limit = 100;
        $offset = ($page - 1) * $limit;

        // get total count of service visitors
        try {
            $count = $this->serviceVisitorRepository->getCountByServiceName($serviceName);
        } catch (Exception $e) {
            $this->errorManager->handleError(
                message: 'error getting service visitors count: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        // check if service visitors found
        if ($count == 0) {
            return [
                'count' => 0,
                'visitors' => [],
                'pagination' => null
            ];
        }

        // get service visitors
        try {
            $serviceVisitors = $this->serviceVisitorRepository->findByServiceName($serviceName, $limit, $offset);
        } catch (Exception $e) {
            $this->errorManager->handleError(
                message: 'error getting service visitors: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        // format service visitors
        $formattedServiceVisitors = [];
        foreach ($serviceVisitors as $serviceVisitor) {
            $lastVisitTime = $serviceVisitor->getLastVisitTime();
            $formattedServiceVisitors[] = [
                'id' => $serviceVisitor->getId(),
                'ip_address' => $serviceVisitor->getIpAddress(),
                'location' => $serviceVisitor->getLocation(),
                'referer' => $serviceVisitor->getReferer(),
                'browser' => $this->visitorInfoUtil->getBrowserShortify($serviceVisitor->getUserAgent() ?? 'Unknown'),
                'os' => $this->visitorInfoUtil->getOs($serviceVisitor->getUserAgent() ?? 'Unknown'),
                'last_visit_time' => $lastVisitTime ? $lastVisitTime->format('Y-m-d H:i:s') : 'Unknown'
            ];
        }

        // calculate total pages
        $totalPages = ceil($count / $limit);

        // return service visitors data
        return [
            'count' => $count,
            'visitors' => $formattedServiceVisitors,
            'pagination' => [
                'currentPage' => $page,
                'totalPages' => $totalPages,
                'limit' => $limit
            ]
        ];
    }

    /**
     * Get referers with count for a given service name
     *
     * @param string $serviceName The service name to search for
     *
     * @return array<string, int> The referers with count
     */
    public function getReferersByServiceName(string $serviceName): array
    {
        $referers = [];

        // get service visitors
        try {
            $serviceVisitors = $this->serviceVisitorRepository->findByServiceName($serviceName);
        } catch (Exception $e) {
            $this->errorManager->handleError(
                message: 'error getting service visitors: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        // check if service visitors found
        if ($serviceVisitors == null) {
            return $referers;
        }

        // get referers with count for a given service name
        foreach ($serviceVisitors as $serviceVisitor) {
            if (!isset($referers[$serviceVisitor->getReferer()])) {
                $referers[$serviceVisitor->getReferer()] = 0;
            }
            $referers[$serviceVisitor->getReferer()]++;
        }

        // sort referers by count
        arsort($referers);

        return $referers;
    }

    /**
     * Update service visitor last visit time to current time
     *
     * @param string $ipAddress The ip address
     * @param string $serviceName The service name
     *
     * @return void
     */
    public function updateServiceVisitorLastVisitTime(string $ipAddress, string $serviceName): void
    {
        // get service visitor entity
        $serviceVisitor = $this->serviceVisitorRepository->findOneBy([
            'ip_address' => $ipAddress,
            'service_name' => $serviceName
        ]);

        // check if service visitor found
        if ($serviceVisitor == null) {
            $this->errorManager->handleError(
                message: 'error to update service visitor last visit time: visitor not found',
                code: Response::HTTP_NOT_FOUND
            );
        }

        // update service visitor last visit time
        $serviceVisitor->setLastVisitTime(new DateTime());

        try {
            // flush service visitor to database
            $this->entityManagerInterface->flush();
        } catch (Exception $e) {
            $this->errorManager->handleError(
                message: 'error to update service visitor last visit time: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Update service visitor user agent
     *
     * @param string $ipAddress The ip address
     * @param string $serviceName The service name
     * @param string $userAgent The new user agent
     *
     * @return void
     */
    public function updateServiceVisitorUserAgent(string $ipAddress, string $serviceName, string $userAgent): void
    {
        // get service visitor entity
        $serviceVisitor = $this->serviceVisitorRepository->findOneBy([
            'ip_address' => $ipAddress,
            'service_name' => $serviceName
        ]);

        // check if service visitor found
        if ($serviceVisitor == null) {
            $this->errorManager->handleError(
                message: 'error to update service visitor user agent: service visitor not found',
                code: Response::HTTP_NOT_FOUND
            );
        }

        // update service visitor user agent
        $serviceVisitor->setUserAgent($userAgent);

        try {
            // flush service visitor to database
            $this->entityManagerInterface->flush();
        } catch (Exception $e) {
            $this->errorManager->handleError(
                message: 'error to update service visitor user agent: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Update service visitor referer
     *
     * @param string $ipAddress The ip address
     * @param string $serviceName The service name
     * @param string $referer The new referer
     *
     * @return void
     */
    public function updateServiceVisitorReferer(string $ipAddress, string $serviceName, string $referer): void
    {
        // get service visitor entity
        $serviceVisitor = $this->serviceVisitorRepository->findOneBy([
            'ip_address' => $ipAddress,
            'service_name' => $serviceName
        ]);

        // check if service visitor found
        if ($serviceVisitor == null) {
            $this->errorManager->handleError(
                message: 'error to update service visitor referer: service visitor not found',
                code: Response::HTTP_NOT_FOUND
            );
        }

        // check if referer is already known
        if ($serviceVisitor->getReferer() != 'Unknown') {
            return;
        }

        // update service visitor referer
        $serviceVisitor->setReferer($referer);

        try {
            // flush service visitor to database
            $this->entityManagerInterface->flush();
        } catch (Exception $e) {
            $this->errorManager->handleError(
                message: 'error to update service visitor referer: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Validate service visitors data
     *
     * @return array<string, int> The validation result
     */
    public function validateServiceVisitors(): array
    {
        $orphanedRemoved = 0;
        $duplicatesRemoved = 0;

        // get all services from config
        $services = $this->serviceManager->getServicesList();
        if ($services == null) {
            $this->errorManager->handleError(
                message: 'error to get monitored services config',
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
        $serviceNames = array_keys($services);

        // get all service visitors
        $allVisitors = $this->serviceVisitorRepository->findAll();

        // identify and remove orphaned visitors
        $orphans = [];
        foreach ($allVisitors as $visitor) {
            if (!in_array($visitor->getServiceName(), $serviceNames)) {
                $orphans[] = $visitor->getId();
                $this->entityManagerInterface->remove($visitor);
                $orphanedRemoved++;
            }
        }
        if ($orphanedRemoved > 0) {
            $this->entityManagerInterface->flush();
        }

        // get table name
        $tableName = $this->databaseManager->getEntityTableName(ServiceVisitor::class);

        // remove duplicate visitors using a raw SQL query
        $connection = $this->entityManagerInterface->getConnection();
        $sql = 'DELETE v1 FROM ' . $tableName . ' v1 INNER JOIN ' . $tableName . ' v2 WHERE v1.id < v2.id AND v1.ip_address = v2.ip_address AND v1.service_name = v2.service_name;';
        $statement = $connection->prepare($sql);
        $result = $statement->executeStatement();
        $duplicatesRemoved = (int) $result;

        // recalculate table IDs (reindex)
        $this->databaseManager->recalculateTableIds($tableName);

        // return validation results
        return [
            'orphaned_removed' => $orphanedRemoved,
            'duplicates_removed' => $duplicatesRemoved
        ];
    }
}
