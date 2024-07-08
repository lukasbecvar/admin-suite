<?php

namespace App\Repository;

use App\Entity\ServiceMonitoring;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Class ServiceMonitoringRepository
 *
 * Repository for the ServiceMonitoring database entity
 *
 * @extends ServiceEntityRepository<ServiceMonitoring>
 *
 * @package App\Repository
 */
class ServiceMonitoringRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ServiceMonitoring::class);
    }
}
