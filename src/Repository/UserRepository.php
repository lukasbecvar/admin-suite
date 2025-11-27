<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * Class UserRepository
 *
 * Repository for User database entity
 *
 * @extends ServiceEntityRepository<User>
 *
 * @package App\Repository
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Finds all tokens from the User entity.
     *
     * @return string[] Returns an array of token strings.
     */
    public function findAllTokens(): array
    {
        return $this->createQueryBuilder('u')
            ->select('u.token')
            ->getQuery()
            ->getSingleColumnResult();
    }
}
