<?php

namespace App\Repository;

use App\Entity\Banned;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * Class BannedRepository
 *
 * Repository for the Banned database entity
 *
 * @extends ServiceEntityRepository<Banned>
 *
 * @package App\Repository
 */
class BannedRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Banned::class);
    }
}
