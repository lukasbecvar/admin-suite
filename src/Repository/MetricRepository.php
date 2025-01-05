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
 * Repository for the Metric database entity
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
            case 'last_week':
                $date = new DateTime('-7 days');
                break;
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

        // aggregate data if time period is 'last_week', 'last_month' or 'all_time'
        if (in_array($timePeriod, ['last_week', 'last_month', 'all_time'])) {
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
}
