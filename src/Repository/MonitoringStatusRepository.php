<?php

namespace App\Repository;

use App\Entity\MonitoringStatus;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * Class MonitoringStatusRepository
 *
 * Repository for the MonitoringStatus database entity
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
}
