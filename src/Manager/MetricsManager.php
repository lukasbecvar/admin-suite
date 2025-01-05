<?php

namespace App\Manager;

use DateTime;
use Exception;
use App\Util\AppUtil;
use App\Entity\Metric;
use App\Util\CacheUtil;
use App\Util\ServerUtil;
use App\Repository\MetricRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class MetricsManager
 *
 * The manager for metrics system functionality
 *
 * @package App\Manager
 */
class MetricsManager
{
    private AppUtil $appUtil;
    private CacheUtil $cacheUtil;
    private ServerUtil $serverUtil;
    private ErrorManager $errorManager;
    private MetricRepository $metricRepository;
    private EntityManagerInterface $entityManagerInterface;

    public function __construct(
        AppUtil $appUtil,
        CacheUtil $cacheUtil,
        ServerUtil $serverUtil,
        ErrorManager $errorManager,
        MetricRepository $metricRepository,
        EntityManagerInterface $entityManagerInterface
    ) {
        $this->appUtil = $appUtil;
        $this->cacheUtil = $cacheUtil;
        $this->serverUtil = $serverUtil;
        $this->errorManager = $errorManager;
        $this->metricRepository = $metricRepository;
        $this->entityManagerInterface = $entityManagerInterface;
    }

    /**
     * Get resource usage metrics
     *
     * @param string $timePeriod The time period
     *
     * @return array<string,mixed> The metrics data
     */
    public function getResourceUsageMetrics(string $timePeriod = 'last_24_hours'): array
    {
        $categories = [];
        $cpuData = [];
        $ramData = [];
        $storageData = [];

        // get usage history metrics
        $cpuUsage = $this->metricRepository->getMetricsByNameAndTimePeriod('cpu_usage', 'host-system', $timePeriod);
        $ramUsage = $this->metricRepository->getMetricsByNameAndTimePeriod('ram_usage', 'host-system', $timePeriod);
        $storageUsage = $this->metricRepository->getMetricsByNameAndTimePeriod('storage_usage', 'host-system', $timePeriod);

        // check if metrics data is iterable
        if (!is_iterable($cpuUsage) || !is_iterable($ramUsage) || !is_iterable($storageUsage)) {
            $this->errorManager->handleError(
                message: 'error to get metrics: return data is not iterable',
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        // get current usages
        $cpuUsageCurrent = $this->serverUtil->getCpuUsage();
        $ramUsageCurrent = $this->serverUtil->getRamUsagePercentage();
        $storageUsageCurrent = $this->serverUtil->getDriveUsagePercentage();

        // format cpu usage value
        if (str_starts_with((string) $cpuUsageCurrent, '0.')) {
            $cpuUsageCurrent = round($cpuUsageCurrent, 1);
        } else {
            $cpuUsageCurrent = intval($cpuUsageCurrent);
        }

        if (in_array($timePeriod, ['last_week', 'last_month', 'all_time'])) {
            // fill categories and cpu usage data (average aggregated)
            foreach ($cpuUsage as $metric) {
                $categories[] = $metric['date'];
                $cpuData[] = (float) $metric['average'];
            }

            // fill ram usage data (average aggregated)
            foreach ($ramUsage as $metric) {
                $ramData[] = (float) $metric['average'];
            }

            // fill storage usage data (average aggregated)
            foreach ($storageUsage as $metric) {
                $storageData[] = (float) $metric['average'];
            }
        } else {
            // fill categories and cpu usage data
            foreach ($cpuUsage as $metric) {
                $categories[] = $metric->getTime()->format('H:i');
                $cpuData[] = (float) $metric->getValue();
            }

            // fill ram usage data
            foreach ($ramUsage as $metric) {
                $ramData[] = (float) $metric->getValue();
            }

            // fill storage usage data
            foreach ($storageUsage as $metric) {
                $storageData[] = (float) $metric->getValue();
            }
        }

        // round times in categories
        $categories = $this->appUtil->roundTimesInArray($categories);

        // build metrics data
        $metricsData = [
            'categories' => $categories,
            'cpu' => [
                'data' => $cpuData,
                'current' => $cpuUsageCurrent
            ],
            'ram' => [
                'data' => $ramData,
                'current' => $ramUsageCurrent
            ],
            'storage' => [
                'data' => $storageData,
                'current' => $storageUsageCurrent
            ]
        ];

        // return metrics data
        return $metricsData;
    }

    /**
     * Save metric value
     *
     * @param string $metricName The metric name
     * @param string $value The metric value
     * @param string $serviceName The metric service name
     *
     * @throws Exception Error to flush metric to database
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
     * Save usage metrics
     *
     * @param float $cpuUsage The CPU usage
     * @param int $ramUsage The RAM usage
     * @param int $storageUsage The storage usage
     *
     * @return void
     */
    public function saveUsageMetrics(float $cpuUsage, int $ramUsage, int $storageUsage): void
    {
        // get monitoring interval
        $monitoringInterval = (int) $this->appUtil->getEnvValue('MONITORING_INTERVAL') * 60;
        $metricsSaveInterval = (int) $this->appUtil->getEnvValue('METRICS_SAVE_INTERVAL') * 60;

        // calculate cache expiration
        $cacheExpiration = $monitoringInterval * 2;

        // define cache keys
        $cpuKey = 'metrics_cpu_usage_sum';
        $ramKey = 'metrics_ram_usage_sum';
        $storageKey = 'metrics_storage_usage_sum';
        $countKey = 'metrics_metric_count';
        $lastSaveKey = 'metrics_last_save_time';

        // get current sums and counts
        $cpuSum = $this->cacheUtil->getValue($cpuKey)->get() ?? 0;
        $ramSum = $this->cacheUtil->getValue($ramKey)->get() ?? 0;
        $storageSum = $this->cacheUtil->getValue($storageKey)->get() ?? 0;
        $count = $this->cacheUtil->getValue($countKey)->get() ?? 0;

        // calculate new sums
        $cpuSum += $cpuUsage;
        $ramSum += $ramUsage;
        $storageSum += $storageUsage;
        $count++;

        // save updated values to cache
        $this->cacheUtil->setValue($cpuKey, $cpuSum, $cacheExpiration);
        $this->cacheUtil->setValue($ramKey, $ramSum, $cacheExpiration);
        $this->cacheUtil->setValue($storageKey, $storageSum, $cacheExpiration);
        $this->cacheUtil->setValue($countKey, $count, $cacheExpiration);

        // if it's more than metrics save interval, save averages and reset values
        if (!$this->cacheUtil->isCatched($lastSaveKey)) {
            $averageCpu = round($cpuSum / $count, 1);
            $averageRam = round($ramSum / $count, 1);
            $averageStorage = round($storageSum / $count, 1);

            // save averages to DB
            $this->saveMetric('cpu_usage', (string) $averageCpu);
            $this->saveMetric('ram_usage', (string) $averageRam);
            $this->saveMetric('storage_usage', (string) $averageStorage);

            // reset metrics cache
            $this->cacheUtil->setValue($cpuKey, 0, $cacheExpiration);
            $this->cacheUtil->setValue($ramKey, 0, $cacheExpiration);
            $this->cacheUtil->setValue($storageKey, 0, $cacheExpiration);
            $this->cacheUtil->setValue($countKey, 0, $cacheExpiration);
        }

        // set last save time if not catched (disable next save)
        if (!$this->cacheUtil->isCatched($lastSaveKey)) {
            $this->cacheUtil->setValue($lastSaveKey, time(), $metricsSaveInterval);
        }
    }

    /**
     * Save service metrics
     *
     * @param string $metricName The name of the metric
     * @param int|float $value The value of the metric
     * @param string $type The type of the metric
     *
     * @return void
     */
    public function saveServicesMetric(string $metricName, int|float $value, string $type = 'web-service'): void
    {
        // get monitoring interval
        $monitoringInterval = (int) $this->appUtil->getEnvValue('MONITORING_INTERVAL') * 60;
        $metricsSaveInterval = (int) $this->appUtil->getEnvValue('METRICS_SAVE_INTERVAL') * 60;

        // calculate cache expiration
        $cacheExpiration = $monitoringInterval * 2;

        // define cache keys
        $metricKey = $metricName . '_' . $type . '_sum';
        $countKey = $metricName . '_' . $type . '_count';
        $lastSaveKey = $metricName . '_' . $type . '_last_save_time';

        // get current sums and counts
        $metricSum = $this->cacheUtil->getValue($metricKey)->get() ?? 0;
        $count = $this->cacheUtil->getValue($countKey)->get() ?? 0;

        // calculate new sums
        $metricSum += $value;
        $count++;

        // save updated values to cache
        $this->cacheUtil->setValue($metricKey, $metricSum, $cacheExpiration);
        $this->cacheUtil->setValue($countKey, $count, $cacheExpiration);

        // if it's more than metrics save interval, save averages and reset values
        if (!$this->cacheUtil->isCatched($lastSaveKey)) {
            $averageValue = round($metricSum / $count, 1);

            // save averages to DB
            $this->saveMetric($metricName, (string) $averageValue, $type);

            // reset metrics cache
            $this->cacheUtil->setValue($metricKey, 0, $cacheExpiration);
            $this->cacheUtil->setValue($countKey, 0, $cacheExpiration);
        }

        // set last save time if not catched (disable next save)
        if (!$this->cacheUtil->isCatched($lastSaveKey)) {
            $this->cacheUtil->setValue($lastSaveKey, time(), $metricsSaveInterval);
        }
    }
}
