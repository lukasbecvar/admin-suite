<?php

namespace App\Repository;

use App\Entity\SLAHistory;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * Class SLAHistoryRepository
 *
 * Repository for SLAHistory database entity
 *
 * @extends ServiceEntityRepository<SLAHistory>
 *
 * @package App\Repository
 */
class SLAHistoryRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SLAHistory::class);
    }
}
