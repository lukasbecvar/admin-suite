<?php

namespace App\Repository;

use App\Entity\Todo;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * Class TodoRepository
 *
 * Repository for the Todo database entity
 *
 * @extends ServiceEntityRepository<Todo>
 *
 * @package App\Repository
 */
class TodoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Todo::class);
    }
}
