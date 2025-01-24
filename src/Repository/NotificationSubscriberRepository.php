<?php

namespace App\Repository;

use App\Entity\NotificationSubscriber;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * Class NotificationSubscriberRepository
 *
 * Repository for NotificationSubscriber database entity
 *
 * @extends ServiceEntityRepository<NotificationSubscriber>
 *
 * @package App\Repository
 */
class NotificationSubscriberRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NotificationSubscriber::class);
    }
}
