<?php

namespace App\Repository;

use App\Entity\Todo;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * Class TodoRepository
 *
 * Repository for Todo database entity
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

    /**
     * Find todos by user ID and status
     *
     * @param int $userId
     * @param string $status
     *
     * @return Todo[] Array of Todo entities
     */
    public function findByUserIdAndStatus(int $userId, string $status): array
    {
        return $this->findBy(
            [
                'user_id' => $userId,
                'status' => $status,
            ],
            ['position' => 'ASC']
        );
    }
}
