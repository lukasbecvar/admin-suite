<?php

namespace App\Manager;

use App\Entity\User;
use App\Util\CookieUtil;
use App\Util\SessionUtil;
use App\Util\SecurityUtil;
use App\Util\VisitorInfoUtil;
use Psr\Cache\CacheItemInterface;
use Symfony\Component\String\ByteString;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class AuthManager
 *
 * Contains methods to manage user authentication.
 *
 * @package App\Manager
 */
class AuthManager
{
    private LogManager $logManager;
    private CookieUtil $cookieUtil;
    private SessionUtil $sessionUtil;
    private UserManager $userManager;
    private CacheManager $cacheManager;
    private ErrorManager $errorManager;
    private SecurityUtil $securityUtil;
    private VisitorInfoUtil $visitorInfoUtil;
    private EntityManagerInterface $entityManager;

    public function __construct(LogManager $logManager, CookieUtil $cookieUtil, SessionUtil $sessionUtil, UserManager $userManager, CacheManager $cacheManager, ErrorManager $errorManager, SecurityUtil $securityUtil, VisitorInfoUtil $visitorInfoUtil, EntityManagerInterface $entityManager)
    {
        $this->logManager = $logManager;
        $this->cookieUtil = $cookieUtil;
        $this->sessionUtil = $sessionUtil;
        $this->userManager = $userManager;
        $this->cacheManager = $cacheManager;
        $this->errorManager = $errorManager;
        $this->securityUtil = $securityUtil;
        $this->entityManager = $entityManager;
        $this->visitorInfoUtil = $visitorInfoUtil;
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
        // check if user already exist
        if ($this->userManager->checkIfUserExist($username)) {
            return;
        }

        // generate entity token
        $token = $this->generateUserToken();

        // hash password
        $password = $this->securityUtil->generateHash($password);

        // get current time
        $time = new \DateTime();

        // get ip address
        $ip_address = $this->visitorInfoUtil->getIP();

        // get user agent
        $user_agent = $this->visitorInfoUtil->getUserAgent();

        // check if ip address is null
        if ($ip_address == null) {
            $ip_address = 'Unknown';
        }

        // check if ip address is null
        if ($user_agent == null) {
            $user_agent = 'Unknown';
        }

        // check if user exist
        if ($this->userManager->getUserRepository(['username' => $username]) == null) {
            try {
                // init user entity
                $user = new User();

                $user->setUsername($username)
                    ->setPassword($password)
                    ->setRole('USER')
                    ->setIpAddress($ip_address)
                    ->setUserAgent($user_agent)
                    ->setToken($token)
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
     * Get current user logged user.
     *
     * @return User|null The user object if found, null otherwise
     */
    public function getLoggedUserRepository(): ?User
    {
        // check if user logged in
        if (!$this->isUserLogedin()) {
            return null;
        }

        // return logged user
        return $this->userManager->getUserRepository(['token' => $this->sessionUtil->getSessionValue('user-token')]);
    }

    public function isLoggedInUserAdmin(): bool
    {
        // check if user logged in
        if (!$this->isUserLogedin()) {
            return false;
        }

        // get logged user
        $user = $this->getLoggedUserRepository();

        // check if user exist
        if ($user == null) {
            return false;
        }

        // check if user is admin
        if ($this->userManager->isUserAdmin((int) $user->getId())) {
            return true;
        }

        return false;
    }

    /**
     * Checks if a user is logged in.
     *
     * @return bool
     */
    public function isUserLogedin(): bool
    {
        // check if session exist
        if (!$this->sessionUtil->checkSession('user-token')) {
            return false;
        }

        // get login token form session
        $loginToken = $this->sessionUtil->getSessionValue('user-token');

        // check if token exist in database
        if ($this->userManager->getUserRepository(['token' => $loginToken]) != null) {
            return true;
        }

        return false;
    }

    /**
     * Check if a user can login.
     *
     * @param string $username The username of the user
     * @param string $password The password of the user
     *
     * @return bool True if the user can login, otherwise false
     */
    public function canLogin(string $username, string $password): bool
    {
        // get user repo
        $repo = $this->userManager->getUserRepository(['username' => $username]);

        // check if user exist
        if ($repo != null) {
            // check if password is correct
            if ($this->securityUtil->verifyPassword($password, (string) $repo->getPassword())) {
                return true;
            }
        } else {
            // log invalid credentials
            $this->logManager->log('authenticator', 'invalid login user: ' . $username . ':' . $password, 2);
        }

        return false;
    }

    /**
     * Login a user.
     *
     * @param string $username The username of the user
     * @param bool $remember Whether to remember the user
     *
     * @return void
     */
    public function login(string $username, bool $remember): void
    {
        // get user repository
        $repo = $this->userManager->getUserRepository(['username' => $username]);

        // check if user exist
        if ($repo != null) {
            // get user token
            $token = (string) $repo->getToken();

            try {
                // set user token session
                $this->sessionUtil->setSession('user-token', $token);

                // save user identifier in session
                $this->sessionUtil->setSession('user-identifier', (string) $repo->getId());

                // set user token cookie
                if ($remember) {
                    $this->cookieUtil->set('user-token', $token, time() + (60 * 60 * 24 * 7 * 365));
                }

                // update user data
                $this->updateDataOnLogin($token);

                // log action
                $this->logManager->log('authenticator', 'login user: ' . $username);
            } catch (\Exception $e) {
                $this->errorManager->handleError('error to login user: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }
    }

    /**
     * Update user data on login.
     *
     * @param string $token The token of the user
     *
     * @throws \Exception If there is an error while updating the user data
     *
     * @return void
     */
    public function updateDataOnLogin(string $token): void
    {
        // get user repository
        $repo = $this->userManager->getUserRepository(['token' => $token]);

        // check if repo found
        if ($repo == null) {
            $this->errorManager->handleError('error to update user data: user not found', Response::HTTP_NOT_FOUND);
            return;
        }

        // update user data
        $repo->setLastLoginTime(new \DateTime());
        $repo->setIpAddress($this->visitorInfoUtil->getIP() ?? 'Unknown');
        $repo->setUserAgent($this->visitorInfoUtil->getUserAgent() ?? 'Unknown');

        // flush updated user data
        try {
            $this->entityManager->flush();
        } catch (\Exception $e) {
            $this->errorManager->handleError('error to update user data: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get the id of the logged in user.
     *
     * @return int The id of the logged in user
     */
    public function getLoggedUserId(): int
    {
        // check if user logged in
        if ($this->isUserLogedin()) {
            // get user token
            $token = $this->getLoggedUserToken();

            // get user repository
            $user = $this->userManager->getUserRepository(['token' => $token]);

            // check if user exist
            if ($user != null) {
                return (int) $user->getId();
            }
        }

        return 0;
    }

    /**
     * Retrieves the login token for the current user session.
     *
     * @return mixed The login token or null if not found or invalid.
     */
    public function getLoggedUserToken(): mixed
    {
        // check if session exist
        if (!$this->sessionUtil->checkSession('user-token')) {
            return null;
        }

        // get login token form session
        $loginToken = $this->sessionUtil->getSessionValue('user-token');

        // check if token exist in database
        if ($this->userManager->getUserRepository(['token' => $loginToken]) != null) {
            return $loginToken;
        }

        return null;
    }

    /**
     * User logout action.
     *
     * @return void
     */
    public function logout(): void
    {
        // check if user logged in
        if ($this->isUserLogedin()) {
            // init user
            $user = $this->userManager->getUserRepository(['token' => $this->getLoggedUserToken()]);

            // check if repo found
            if ($user == null) {
                $this->errorManager->handleError('error to update user data: user not found', Response::HTTP_NOT_FOUND);
                return;
            }

            // log logout event
            $this->logManager->log('authenticator', 'logout user: ' . $user->getUsername());

            // unset login cookie
            $this->cookieUtil->unset('user-token');

            // unset login session
            $this->sessionUtil->destroySession();
        }
    }

    /**
     * Regenerate tokens for all users in the database.
     *
     * This method regenerates tokens for all users in the database, ensuring uniqueness for each token.
     *
     * @return array<bool|null|string> An array containing the status of the operation and any relevant message.
     * - The 'status' key indicates whether the operation was successful (true) or not (false).
     * - The 'message' key contains any relevant error message if the operation failed, otherwise it is null.
     */
    public function regenerateUsersTokens(): array
    {
        $state = [
            'status' => true,
            'message' => null
        ];

        // get all users in database
        $userRepo = $this->entityManager->getRepository(User::class)->findAll();

        // regenerate all users tokens
        foreach ($userRepo as $user) {
            // regenerate new token
            $newToken = $this->generateUserToken();

            // set new token
            $user->setToken($newToken);

            // flush data
            try {
                $this->entityManager->flush();
            } catch (\Exception $e) {
                $state = [
                    'status' => false,
                    'message' => $e->getMessage()
                ];
            }
        }

        return $state;
    }

    /**
     * Generate a unique token for a user.
     *
     * @return string The generated user token.
     */
    public function generateUserToken(): string
    {
        // generate user token
        $token = ByteString::fromRandom(32)->toString();

        // get users repository
        $userRepo = $this->entityManager->getRepository(User::class);

        // check if user token is not already used
        if ($userRepo->findOneBy(['token' => $token]) != null) {
            $this->generateUserToken();
        }

        return $token;
    }

    /**
     * Store online user id in cache
     *
     * @param int $userId The id of the user to store.
     *
     * @return void
     */
    public function cacheOnlineUser(int $userId): void
    {
        // cache online visitor
        $this->cacheManager->setValue('online_user_' . $userId, 'online', 300);
    }

    /**
     * Get online users list.
     *
     * @return array<mixed> The list of online users.
     */
    public function getOnlineUsersList(): array
    {
        $onlineVisitors = [];

        // get all users list
        $users = $this->userManager->getAllUsersRepository();

        // Check if $users is iterable
        if (!is_iterable($users)) {
            // Handle the case where $users is not iterable, maybe log an error
            // or return an empty array if it's acceptable.
            return $onlineVisitors;
        }

        // check all users status
        foreach ($users as $user) {
            // Check if $user is an object with getId() method
            if (is_object($user) && method_exists($user, 'getId')) {
                // get visitor status
                $status = $this->getUserStatus($user->getId());

                // check visitor status
                if ($status == 'online') {
                    array_push($onlineVisitors, $user);
                }
            }
        }

        return $onlineVisitors;
    }

    /**
     * Get user status.
     *
     * @param int $userId The id of the user.
     *
     * @return string The status of the user.
     */
    public function getUserStatus(int $userId): string
    {
        $userCacheKey = 'online_user_' . $userId;

        // get the cache item
        $cacheItem = $this->cacheManager->getValue($userCacheKey);

        // check if cache item exists and is not expired
        if ($cacheItem instanceof CacheItemInterface) {
            // retrieve the value from the cache item
            $status = $cacheItem->get();

            // check if status found
            if (is_string($status) && $status !== null && $status !== '') {
                return $status;
            }
        }

        return 'offline';
    }
}