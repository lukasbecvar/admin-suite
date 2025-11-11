<?php

namespace App\Repository;

use App\Entity\SentNotificationLog;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * Class SentNotificationLogRepository
 *
 * Repository for SentNotificationLog database entity
 *
 * @extends ServiceEntityRepository<SentNotificationLog>
 *
 * @package App\Repository
 */
class SentNotificationLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SentNotificationLog::class);
    }
}
