<?php

namespace App\Repository;

use DateTime;
use Exception;
use App\Entity\Metric;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * Class MetricRepository
 *
 * Repository for Metric database entity
 *
 * @extends ServiceEntityRepository<Metric>
 *
 * @package App\Repository
 */
class MetricRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Metric::class);
    }

    /**
     * Find metrics by name and service name
     *
     * @param string $metricName The metric name
     * @param string $serviceName The service name
     *
     * @return Metric[] The metrics
     */
    public function findMetricsByNameAndService(string $metricName, string $serviceName): array
    {
        $qb = $this->createQueryBuilder('m');
        return $qb
            ->where('m.name = :name')
            ->andWhere('m.service_name = :serviceName')
            ->setParameter('name', $metricName)
            ->setParameter('serviceName', $serviceName)
            ->getQuery()
            ->getResult();
    }

    /**
     * Get recent metrics that should be preserved
     *
     * @param DateTime $cutoffDate The cutoff date
     *
     * @return array<array<string, mixed>> Array of recent metrics data
     */
    public function getRecentMetrics(DateTime $cutoffDate): array
    {
        $qb = $this->createQueryBuilder('m');

        $recentMetrics = $qb
            ->select('m.name', 'm.value', 'm.service_name', 'm.time')
            ->where('m.time >= :cutoffDate')
            ->setParameter('cutoffDate', $cutoffDate)
            ->orderBy('m.time', 'ASC')
            ->getQuery()
            ->getArrayResult();

        return $recentMetrics;
    }

    /**
     * Get old metrics that need aggregation
     *
     * @param DateTime $cutoffDate The cutoff date
     *
     * @return array<Metric> Array of old metrics
     */
    public function getOldMetrics(DateTime $cutoffDate): array
    {
        $qb = $this->createQueryBuilder('m');

        return $qb
            ->where('m.time < :cutoffDate')
            ->setParameter('cutoffDate', $cutoffDate)
            ->orderBy('m.time', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Get metrics by name and time period
     *
     * @param string $name The name of the metric
     * @param string $serviceName The service name of the metric
     * @param string $timePeriod The time period for selecting metrics
     *
     * @return mixed The metrics data
     */
    public function getMetricsByNameAndTimePeriod(string $name, string $serviceName, string $timePeriod): mixed
    {
        $qb = $this->createQueryBuilder('m')
            ->where('m.name = :name')
            ->andWhere('m.service_name = :service_name')
            ->setParameter('name', $name)
            ->setParameter('service_name', $serviceName);

        // define time filter based on $timePeriod value
        switch ($timePeriod) {
            case 'last_24_hours':
                $date = new DateTime('-24 hours');
                break;
            case 'last_7_days':
            case 'last_week':
                $date = new DateTime('-7 days');
                break;
            case 'last_30_days':
            case 'last_month':
                $date = new DateTime('-30 days');
                break;
            case 'all_time':
            default:
                $date = null;
                break;
        }

        // add time filter only if $date is not null
        if ($date) {
            $qb->andWhere('m.time >= :date')
                ->setParameter('date', $date);
        }

        // order by time, from newest to oldest
        $qb->orderBy('m.time', 'ASC');

        // get metrics
        $metrics = $qb->getQuery()->getResult();

        // aggregate data if time period is 'last_7_days', 'last_30_days', 'last_week', 'last_month' or 'all_time'
        if (in_array($timePeriod, ['last_7_days', 'last_week', 'last_30_days', 'last_month', 'all_time'])) {
            $aggregatedData = [];

            // check if metrics data is iterable
            if (!is_iterable($metrics)) {
                throw new Exception('error to get metrics: return data is not iterable');
            }

            // aggregate data by month for 'all_time', by day for other time periods
            foreach ($metrics as $metric) {
                if ($timePeriod === 'all_time') {
                    $dateKey = $metric->getTime()->format('Y-m');
                } else {
                    $dateKey = $metric->getTime()->format('Y-m-d');
                }
                if (!isset($aggregatedData[$dateKey])) {
                    $aggregatedData[$dateKey] = ['total' => 0, 'count' => 0];
                }
                $aggregatedData[$dateKey]['total'] += (float) $metric->getValue();
                $aggregatedData[$dateKey]['count']++;
            }

            // create average for each month (for 'all_time') or day (for other time periods)
            $result = [];
            foreach ($aggregatedData as $dateKey => $data) {
                $result[] = [
                    'date' => $dateKey,
                    'average' => round($data['total'] / $data['count'], 1)
                ];
            }
            return $result;
        }

        return $metrics;
    }

    /**
     * Get metrics by service name and time period
     *
     * @param string $serviceName The service name of the metric
     * @param string $timePeriod The time period for selecting metrics
     *
     * @return array<mixed> The metrics data
     */
    public function getMetricsByServiceName(string $serviceName, string $timePeriod = 'last_24_hours'): array
    {
        $qb = $this->createQueryBuilder('m')
            ->where('m.service_name = :service_name')
            ->setParameter('service_name', $serviceName);

        // define time filter based on time period
        switch ($timePeriod) {
            case 'last_24_hours':
                $date = new DateTime('-24 hours');
                break;
            case 'last_7_days':
            case 'last_week':
                $date = new DateTime('-7 days');
                break;
            case 'last_30_days':
            case 'last_month':
                $date = new DateTime('-30 days');
                break;
            case 'all_time':
            default:
                $date = null;
                break;
        }

        // add time filter only if $date is not null
        if ($date) {
            $qb->andWhere('m.time >= :date')->setParameter('date', $date);
        }

        // order by time, from oldest to newest
        $qb->orderBy('m.time', 'ASC');

        // get metrics
        $metrics = $qb->getQuery()->getResult();

        // group metrics by name (for each metric name like cpu-usage, ram-usage)
        $groupedMetrics = [];
        foreach ($metrics as $metric) {
            $name = $metric->getName();
            $time = $metric->getTime();

            // get date key based on time period
            if ($timePeriod === 'last_24_hours') {
                $dateKey = $time->format('H:i');
            } elseif ($timePeriod === 'last_7_days' || $timePeriod === 'last_week') {
                $dateKey = $time->format('m/d');
            } elseif ($timePeriod === 'last_30_days' || $timePeriod === 'last_month') {
                $dateKey = $time->format('m/d');
            } elseif ($timePeriod === 'all_time') {
                $dateKey = $time->format('Y/m');
            } else {
                $dateKey = $time->format('Y/m/d H:i');
            }

            // initialize metric data if not already set
            if (!isset($groupedMetrics[$name])) {
                $groupedMetrics[$name] = [];
            }

            // initialize date group if not already set
            if (!isset($groupedMetrics[$name][$dateKey])) {
                $groupedMetrics[$name][$dateKey] = ['total' => 0, 'count' => 0];
            }

            // aggregate metric value
            $groupedMetrics[$name][$dateKey]['total'] += (float) $metric->getValue();
            $groupedMetrics[$name][$dateKey]['count']++;
        }

        // prepare final results with average values
        $result = [];
        foreach ($groupedMetrics as $metricName => $metricData) {
            foreach ($metricData as $dateKey => $data) {
                $result[$metricName][] = [
                    'value' => round($data['total'] / $data['count'], 1),
                    'time' => $dateKey
                ];
            }
        }

        // filter results based on time period to return only required number of values
        if ($timePeriod === 'last_7_days' || $timePeriod === 'last_week') {
            foreach ($result as $metricName => $metricValues) {
                // return only 7 days history
                $result[$metricName] = array_slice($metricValues, 0, 7);
            }
        } elseif ($timePeriod === 'last_30_days' || $timePeriod === 'last_month') {
            foreach ($result as $metricName => $metricValues) {
                // return only 31 days history
                $result[$metricName] = array_slice($metricValues, 0, 31);
            }
        } elseif ($timePeriod === 'all_time') {
            foreach ($result as $metricName => $metricValues) {
                // return only monthly values for all time
                $result[$metricName] = array_slice($metricValues, 0, count($metricValues));
            }
        }

        return $result;
    }
}
