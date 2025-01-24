<?php

namespace App\Repository;

use App\Entity\MonitoringStatus;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * Class MonitoringStatusRepository
 *
 * Repository for MonitoringStatus database entity
 *
 * @extends ServiceEntityRepository<MonitoringStatus>
 *
 * @package App\Repository
 */
class MonitoringStatusRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MonitoringStatus::class);
    }

    /**
     * Find all entities by non current timeframe
     *
     * @param string $currentTimeframe
     *
     * @return MonitoringStatus[] Array of MonitoringStatus entities
     */
    public function findByNonCurrentTimeframe(string $currentTimeframe): array
    {
        return $this->createQueryBuilder('ms')
            ->where('ms.sla_timeframe != :currentTimeframe')
            ->setParameter('currentTimeframe', $currentTimeframe)
            ->getQuery()
            ->getResult();
    }
}
