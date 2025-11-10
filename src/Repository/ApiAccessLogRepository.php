<?php

namespace App\Repository;

use App\Entity\ApiAccessLog;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * Class ApiAccessLogRepository
 *
 * Repository for ApiAccessLog database entity
 *
 * @extends ServiceEntityRepository<ApiAccessLog>
 *
 * @package App\Repository
 */
class ApiAccessLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ApiAccessLog::class);
    }
}
