<?php

namespace App\Manager;

use Exception;
use App\Entity\User;
use App\Util\AppUtil;
use App\Util\SecurityUtil;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class UserManager
 *
 * Manager for user system functionality
 *
 * @package App\Manager
 */
class UserManager
{
    private AppUtil $appUtil;
    private LogManager $logManager;
    private SecurityUtil $securityUtil;
    private ErrorManager $errorManager;
    private UserRepository $userRepository;
    private EntityManagerInterface $entityManager;

    public function __construct(
        AppUtil $appUtil,
        LogManager $logManager,
        SecurityUtil $securityUtil,
        ErrorManager $errorManager,
        UserRepository $userRepository,
        EntityManagerInterface $entityManager
    ) {
        $this->appUtil = $appUtil;
        $this->logManager = $logManager;
        $this->errorManager = $errorManager;
        $this->securityUtil = $securityUtil;
        $this->entityManager = $entityManager;
        $this->userRepository = $userRepository;
    }

    /**
     * Get user from repository by search criteria
     *
     * @param array<mixed> $search The search criteria
     *
     * @return User|null The user object if found, null otherwise
     */
    public function getUserRepository(array $search): ?User
    {
        // get user repo
        return $this->userRepository->findOneBy($search);
    }

    /**
     * Get all users from repository
     *
     * @return array<User> The user object if found, null otherwise
     */
    public function getAllUsersRepositories(): array
    {
        return $this->userRepository->findAll();
    }

    /**
     * Get user from repository by username
     *
     * @param string $username The username of user to retrieve
     *
     * @return User|null The user object if found, null otherwise
     */
    public function getUserByUsername(string $username): ?User
    {
        return $this->userRepository->findOneBy(['username' => $username]);
    }

    /**
     * Get user from repository by ID
     *
     * @param int $userId The ID of the user to retrieve
     *
     * @return User|null The user object if found, null otherwise
     */
    public function getUserById(int $userId): ?User
    {
        return $this->userRepository->find($userId);
    }

    /**
     * Get user from repository by token
     *
     * @param string $token The token of the user to retrieve
     *
     * @return User|null The user object if found, null otherwise
     */
    public function getUserByToken(string $token): ?User
    {
        return $this->userRepository->findOneBy(['token' => $token]);
    }

    /**
     * Get user reference
     *
     * @param int $userId The id of user
     *
     * @return User|null The user reference or null if not found
     */
    public function getUserReference(int $userId): ?User
    {
        if ($userId <= 0) {
            return null;
        }

        return $this->entityManager->getReference(User::class, $userId);
    }

    /**
     * Get all users count from repository
     *
     * @return int|null The user object if found, null otherwise
     */
    public function getUsersCount(): ?int
    {
        return $this->userRepository->count([]);
    }

    /**
     * Get all users from repository by page
     *
     * @param int $page The users list page number
     *
     * @return array<mixed> The user object if found, null otherwise
     */
    public function getUsersByPage(int $page = 1): ?array
    {
        // get page limitter
        $perPage = (int) $this->appUtil->getEnvValue('LIMIT_CONTENT_PER_PAGE');

        // calculate offset
        $offset = ($page - 1) * $perPage;

        // get user repo
        return $this->userRepository->findBy(
            criteria: [],
            orderBy: null,
            limit: $perPage,
            offset: $offset
        );
    }

    /**
     * Check if user exists in database
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
     * Check if user exists by ID
     *
     * @param int $userId The id of the user to check
     *
     * @return bool True if the user exists, otherwise false
     */
    public function checkIfUserExistById(int $userId): bool
    {
        return $this->getUserRepository(['id' => $userId]) != null;
    }

    /**
     * Get username by ID
     *
     * @param int $userId The id of user to get username
     *
     * @return string The username of the user
     */
    public function getUsernameById(int $userId): ?string
    {
        $repo = $this->getUserRepository(['id' => $userId]);

        // check if user exist
        if ($repo != null) {
            return $repo->getUsername();
        }

        return null;
    }

    /**
     * Get user role by ID
     *
     * @param int $userId The user ID
     *
     * @return string The role of the user
     */
    public function getUserRoleById(int $userId): ?string
    {
        $repo = $this->getUserRepository(['id' => $userId]);

        // check if user exist
        if ($repo != null) {
            return $repo->getRole();
        }

        return null;
    }

    /**
     * Check if specified user is admin
     *
     * @param int $userId The id of the user to check the admin role
     *
     * @return bool True if the user has the admin role, otherwise false
     */
    public function isUserAdmin(int $userId): bool
    {
        $role = $this->getUserRoleById($userId);

        // check if user has admin role
        if ($role == 'ADMIN' || $role == 'DEVELOPER' || $role == 'OWNER') {
            return true;
        }

        return false;
    }

    /**
     * Update user role
     *
     * @param int $userId The id of the user to add the admin role
     * @param string $role The role to add
     *
     * @return void
     */
    public function updateUserRole(int $userId, string $role): void
    {
        // get user repo
        $repo = $this->getUserRepository(['id' => $userId]);

        // convert new user role to uppercase
        $role = strtoupper($role);

        // check if user exist
        if ($repo != null) {
            try {
                // update role
                $repo->setRole(strtoupper($role));

                // flush updated user data
                $this->entityManager->flush();
            } catch (Exception $e) {
                $this->errorManager->handleError(
                    message: 'error to grant admin permissions: ' . $e->getMessage(),
                    code: Response::HTTP_INTERNAL_SERVER_ERROR
                );
            }

            // log role update event
            $this->logManager->log(
                name: 'user-manager',
                message: 'update role (' . $role . ') for user: ' . $repo->getUsername(),
                level: LogManager::LEVEL_WARNING
            );
        }
    }

    /**
     * Check if user repository is empty
     *
     * @return bool True if user repository is empty, false otherwise
     */
    public function isUsersEmpty(): bool
    {
        $repository = $this->userRepository;

        // get users count
        $count = $repository->createQueryBuilder('p')->select('COUNT(p.id)')->getQuery()->getSingleScalarResult();

        // check if count is zero
        if ($count == 0) {
            return true;
        }

        return false;
    }

    /**
     * Delete user by ID
     *
     * @param int $userId The user ID
     *
     * @return void
     */
    public function deleteUser(int $userId): void
    {
        // get user repo
        $repo = $this->getUserRepository(['id' => $userId]);

        // check if user exist
        if ($repo != null) {
            try {
                // delete user
                $this->entityManager->remove($repo);
                $this->entityManager->flush();
            } catch (Exception $e) {
                $this->errorManager->handleError(
                    message: 'error to delete user: ' . $e->getMessage(),
                    code: Response::HTTP_INTERNAL_SERVER_ERROR
                );
            }

            // log user delete event
            $this->logManager->log(
                name: 'user-manager',
                message: 'user: ' . $repo->getUsername() . ' deleted',
                level: LogManager::LEVEL_WARNING
            );
        }
    }

    /**
     * Update username
     *
     * @param int $userId The user ID
     * @param string $newUsername The new username
     *
     * @return void
     */
    public function updateUsername(int $userId, string $newUsername): void
    {
        // get user repo
        $repo = $this->getUserRepository(['id' => $userId]);

        // check if user exist
        if ($repo != null) {
            try {
                // get old username
                $oldUsername = $repo->getUsername();

                // update username
                $repo->setUsername($newUsername);

                // flush updated user data
                $this->entityManager->flush();
            } catch (Exception $e) {
                $this->errorManager->handleError(
                    message: 'error to update username: ' . $e->getMessage(),
                    code: Response::HTTP_INTERNAL_SERVER_ERROR
                );
            }

            // log username update event
            $this->logManager->log(
                name: 'account-settings',
                message: 'update username (' . $newUsername . ') for user: ' . $oldUsername,
                level: LogManager::LEVEL_INFO
            );
        }
    }

    /**
     * Update user password
     *
     * @param int $userId The user ID
     * @param string $newPassword The new password
     *
     * @return void
     */
    public function updatePassword(int $userId, string $newPassword): void
    {
        // get user repo
        $repo = $this->getUserRepository(['id' => $userId]);

        // check if user exist
        if ($repo != null) {
            try {
                // hash new password
                $passwordHash = $this->securityUtil->generateHash($newPassword);

                // update password
                $repo->setPassword($passwordHash);

                // flush updated user data
                $this->entityManager->flush();
            } catch (Exception $e) {
                $this->errorManager->handleError(
                    message: 'error to update password: ' . $e->getMessage(),
                    code: Response::HTTP_INTERNAL_SERVER_ERROR
                );
            }

            // log password update event
            $this->logManager->log(
                name: 'account-settings',
                message: 'update password for user: ' . $repo->getUsername(),
                level: LogManager::LEVEL_INFO
            );
        }
    }

    /**
     * Update user profile picture
     *
     * @param int $userId The user ID
     * @param string $newProfilePicture The new profile picture (base64 encoded)
     *
     * @return void
     */
    public function updateProfilePicture(int $userId, string $newProfilePicture): void
    {
        // get user repo
        $repo = $this->getUserRepository(['id' => $userId]);

        // check if user exist
        if ($repo != null) {
            try {
                // update profile picture
                $repo->setProfilePic($newProfilePicture);

                // flush updated user data
                $this->entityManager->flush();
            } catch (Exception $e) {
                $this->errorManager->handleError(
                    message: 'error to update profile picture: ' . $e->getMessage(),
                    code: Response::HTTP_INTERNAL_SERVER_ERROR
                );
            }

            // log profile picture update event
            $this->logManager->log(
                name: 'account-settings',
                message: 'update profile picture for user: ' . $repo->getUsername(),
                level: LogManager::LEVEL_INFO
            );
        }
    }

    /**
     * Update API access status for a user
     *
     * @param int $userId The user ID
     * @param bool $allowApiAccess The new API access status
     * @param string $source The action origin (account-settings or user-manager)
     *
     * @return void
     */
    public function updateApiAccessStatus(int $userId, bool $allowApiAccess, string $source = 'account-settings'): void
    {
        // get user repo
        $repo = $this->getUserRepository(['id' => $userId]);

        // check if user exist
        if ($repo != null) {
            try {
                // update api access status
                $repo->setAllowApiAccess($allowApiAccess);

                // flush updated user data
                $this->entityManager->flush();
            } catch (Exception $e) {
                $this->errorManager->handleError(
                    message: 'error to update api access status: ' . $e->getMessage(),
                    code: Response::HTTP_INTERNAL_SERVER_ERROR
                );
            }

            // log api access update event
            $statusLabel = $allowApiAccess ? 'enabled' : 'disabled';
            $logChannel = $source === 'user-manager' ? 'user-manager' : 'account-settings';
            $logLevel = $source === 'user-manager' ? LogManager::LEVEL_WARNING : LogManager::LEVEL_INFO;

            // log action event
            $this->logManager->log(
                name: $logChannel,
                message: 'api access ' . $statusLabel . ' for user: ' . $repo->getUsername(),
                level: $logLevel
            );
        }
    }
}
