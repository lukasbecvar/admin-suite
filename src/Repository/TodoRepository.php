<?php

namespace App\Repository;

use App\Entity\Todo;
use App\Entity\User;
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
        if ($userId <= 0) {
            return [];
        }

        // get user reference
        $userReference = $this->getEntityManager()->getReference(User::class, $userId);

        // find todos by user reference and status
        return $this->findBy([
            'user' => $userReference,
            'status' => $status
            ], ['position' => 'ASC']);
    }
}
