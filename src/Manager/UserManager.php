<?php

namespace App\Manager;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class UserManager
 *
 * Contains methods to manage user data.
 *
 * @package App\Manager
 */
class UserManager
{
    private LogManager $logManager;
    private ErrorManager $errorManager;
    private EntityManagerInterface $entityManager;

    public function __construct(LogManager $logManager, ErrorManager $errorManager, EntityManagerInterface $entityManager)
    {
        $this->logManager = $logManager;
        $this->errorManager = $errorManager;
        $this->entityManager = $entityManager;
    }

    /**
     * Retrieve a user from the repository based on search criteria.
     *
     * @param array<mixed> $search The search criteria
     *
     * @return User|null The user object if found, null otherwise
     */
    public function getUserRepository(array $search): ?User
    {
        // get user repo
        return $this->entityManager->getRepository(User::class)->findOneBy($search);
    }

    /**
     * Retrieve all users from the repository.
     *
     * @return array<mixed> The user object if found, null otherwise
     */
    public function getAllUsersRepository(): ?array
    {
        return $this->entityManager->getRepository(User::class)->findAll();
    }

    /**
     * Check if a user exists.
     *
     * @param string $username The username to check
     *
     * @return bool True if the user exists, otherwise false
     */
    public function checkIfUserExist(string $username): bool
    {
        return $this->getUserRepository(['username' => $username]) != null;
    }

    /**
     * Get the username of a user.
     *
     * @param int $id The id of the user to get the username
     *
     * @return string The username of the user
     */
    public function getUsernameById(int $id): ?string
    {
        $repo = $this->getUserRepository(['id' => $id]);

        // check if user exist
        if ($repo != null) {
            return $repo->getUsername();
        }

        return null;
    }

    /**
     * Get the role of a user.
     *
     * @param int $id The id of the user to get the role
     *
     * @return string The role of the user
     */
    public function getUserRoleById(int $id): ?string
    {
        $repo = $this->getUserRepository(['id' => $id]);

        // check if user exist
        if ($repo != null) {
            return $repo->getRole();
        }

        return null;
    }

    /**
     * Checks if the specified user has the admin role.
     *
     * @param int $id The id of the user to check the admin role
     *
     * @return bool True if the user has the admin role, otherwise false
     */
    public function isUserAdmin(int $id): bool
    {
        $role = $this->getUserRoleById($id);

        // check if user has admin role
        if ($role == 'ADMIN' || $role == 'DEVELOPER' || $role == 'OWNER') {
            return true;
        }

        return false;
    }

    /**
     * Update the role of a user.
     *
     * @param int $id The id of the user to add the admin role
     * @param string $role The role to add
     *
     * @throws \Exception If there is an error while adding the admin role
     *
     * @return void
     */
    public function updateUserRole(int $id, string $role): void
    {
        // get user repo
        $repo = $this->getUserRepository(['id' => $id]);

        // check if user exist
        if ($repo != null) {
            try {
                // update role
                $repo->setRole(strtoupper($role));

                // flush updated user data
                $this->entityManager->flush();

                // log action
                $this->logManager->log('role-update', 'update role (' . $role . ') for user: ' . $repo->getUsername());
            } catch (\Exception $e) {
                $this->errorManager->handleError('error to grant admin permissions: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }
    }

    /**
     * Checks if the user repository is empty.
     *
     * @return bool True if the user repository is empty, false otherwise.
     */
    public function isUsersEmpty(): bool
    {
        $repository = $this->entityManager->getRepository(User::class);

        // get users count
        $count = $repository->createQueryBuilder('p')->select('COUNT(p.id)')->getQuery()->getSingleScalarResult();

        // check if count is zero
        if ($count == 0) {
            return true;
        }

        return false;
    }
}
