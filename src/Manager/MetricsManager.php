<?php

namespace App\Manager;

use DateTime;
use Exception;
use App\Entity\Metric;
use App\Util\ServerUtil;
use App\Repository\MetricRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class MetricsManager
 *
 * The manager for methods with metrics database
 *
 * @package App\Manager
 */
class MetricsManager
{
    private ServerUtil $serverUtil;
    private ErrorManager $errorManager;
    private MetricRepository $metricRepository;
    private EntityManagerInterface $entityManagerInterface;

    public function __construct(
        ServerUtil $serverUtil,
        ErrorManager $errorManager,
        MetricRepository $metricRepository,
        EntityManagerInterface $entityManagerInterface
    ) {
        $this->serverUtil = $serverUtil;
        $this->errorManager = $errorManager;
        $this->metricRepository = $metricRepository;
        $this->entityManagerInterface = $entityManagerInterface;
    }

    /**
     * Get metrics data
     *
     * @param string $timePeriod The time period
     *
     * @return array<string,mixed> The metrics data
     */
    public function getMetrics(string $timePeriod = 'last_24_hours'): array
    {
        $categories = [];
        $cpuData = [];
        $ramData = [];
        $storageData = [];

        // get usage history metrics
        $cpuUsage = $this->metricRepository->getMetricsByNameAndTimePeriod('cpu_usage', $timePeriod);
        $ramUsage = $this->metricRepository->getMetricsByNameAndTimePeriod('ram_usage', $timePeriod);
        $storageUsage = $this->metricRepository->getMetricsByNameAndTimePeriod('storage_usage', $timePeriod);

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

        // retrun metrics data
        return [
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
    }

    /**
     * Save metrics
     *
     * @param float $cpuUsage The cpu usage
     * @param int $ramUsage The ram usage
     * @param int $storageUsage The storage usage
     *
     * @return void
     */
    public function saveMetrics(float $cpuUsage, int $ramUsage, int $storageUsage): void
    {
        // save cpu usage
        $cpuUsageMetric = new Metric();
        $cpuUsageMetric->setName('cpu_usage')
            ->setValue(strval($cpuUsage))
            ->setTime(new DateTime());

        // save ram usage
        $ramUsageMetric = new Metric();
        $ramUsageMetric->setName('ram_usage')
            ->setValue(strval($ramUsage))
            ->setTime(new DateTime());

        // save storage usage
        $storageUsageMetric = new Metric();
        $storageUsageMetric->setName('storage_usage')
            ->setValue(strval($storageUsage))
            ->setTime(new DateTime());

        // persist metrics
        $this->entityManagerInterface->persist($cpuUsageMetric);
        $this->entityManagerInterface->persist($ramUsageMetric);
        $this->entityManagerInterface->persist($storageUsageMetric);

        // flush changes to database
        try {
            $this->entityManagerInterface->flush();
        } catch (Exception $e) {
            $this->errorManager->handleError(
                message: 'error to flush metrics: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }
}
