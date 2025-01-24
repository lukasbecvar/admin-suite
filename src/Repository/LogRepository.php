<?php

namespace App\Repository;

use App\Entity\Log;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * Class LogRepository
 *
 * Repository for Log database entity
 *
 * @extends ServiceEntityRepository<Log>
 *
 * @package App\Repository
 */
class LogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Log::class);
    }
}
