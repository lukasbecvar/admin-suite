<?php

namespace App\Manager;

use App\Entity\User;
use App\Util\SecurityUtil;
use App\Util\VisitorInfoUtil;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\String\ByteString;
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
    private SecurityUtil $securityUtil;
    private VisitorInfoUtil $visitorInfoUtil;
    private EntityManagerInterface $entityManager;

    public function __construct(LogManager $logManager, ErrorManager $errorManager, SecurityUtil $securityUtil, EntityManagerInterface $entityManager, VisitorInfoUtil $visitorInfoUtil)
    {
        $this->logManager = $logManager;
        $this->errorManager = $errorManager;
        $this->securityUtil = $securityUtil;
        $this->entityManager = $entityManager;
        $this->visitorInfoUtil = $visitorInfoUtil;
    }

    /**
     * Retrieve a user from the repository based on search criteria.
     *
     * @param array<mixed> $search The search criteria
     *
     * @return User|null The user object if found, null otherwise
     */
    public function getUserRepo(array $search): ?User
    {
        // get user repo
        return $this->entityManager->getRepository(User::class)->findOneBy($search);
    }

    /**
     * Register a new user.
     *
     * @param string $username The username of the new user
     * @param string $password The password of the new user
     *
     * @throws \Exception If there is an error while registering the new user
     *
     * @return void
     */
    public function registerUser(string $username, string $password): void
    {
        // generate entity token
        $token = ByteString::fromRandom(32)->toString();

        // check if token not exist
        if ($this->getUserRepo(['token' => $token]) != null) {
            $this->registerUser($username, $password);
        }

        // hash password
        $password = $this->securityUtil->generateHash($password);

        // get current time
        $time = new \DateTime();

        // get ip address
        $ip_address = $this->visitorInfoUtil->getIP();

        // check if ip address is null
        if ($ip_address == null) {
            $ip_address = 'Unknown';
        }

        // check if user exist
        if ($this->getUserRepo(['username' => $username]) == null) {
            try {
                // init user entity
                $user = new User();

                $user->setUsername($username)
                    ->setPassword($password)
                    ->setRole('USER')
                    ->setIpAddress($ip_address)
                    ->setToken(md5(random_bytes(32)))
                    ->setProfilePic('default_pic')
                    ->setRegisterTime($time)
                    ->setLastLoginTime($time);

                // flush user to database
                $this->entityManager->persist($user);
                $this->entityManager->flush();

                // log action
                $this->logManager->log('authenticator', 'new registration user: ' . $username);
            } catch (\Exception $e) {
                $this->errorManager->handleError('error to register new user: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }
    }

    /**
     * Get the username of a user.
     *
     * @param int $id The id of the user to get the username
     *
     * @return string The username of the user
     */
    public function getUsername(int $id): ?string
    {
        $repo = $this->getUserRepo(['id' => $id]);

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
    public function getUserRole(int $id): ?string
    {
        $repo = $this->getUserRepo(['id' => $id]);

        // check if user exist
        if ($repo != null) {
            return $repo->getRole();
        }

        return null;
    }

    /**
     * Get the token of a user.
     *
     * @param int $id The id of the user to get the token
     *
     * @return string The token of the user
     */
    public function getUserToken(int $id): ?string
    {
        $repo = $this->getUserRepo(['id' => $id]);

        // check if user exist
        if ($repo != null) {
            return $repo->getToken();
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
        $role = $this->getUserRole($id);

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
        $repo = $this->getUserRepo(['id' => $id]);

        // check if user exist
        if ($repo != null) {
            try {
                // update role
                $repo->setRole(strtoupper($role));

                // flush updated user data
                $this->entityManager->flush();

                // log action
                $this->logManager->log('role-granted', 'role admin granted to user: ' . $this->getUsername($id));
            } catch (\Exception $e) {
                $this->errorManager->handleError('error to grant admin permissions: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }
    }
}
