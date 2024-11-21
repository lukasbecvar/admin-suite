<?php

namespace App\Manager;

use DateTime;
use Exception;
use App\Entity\User;
use App\Util\AppUtil;
use App\Util\CacheUtil;
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
 * User authentication and authorization functionality
 *
 * @package App\Manager
 */
class AuthManager
{
    private AppUtil $appUtil;
    private CacheUtil $cacheUtil;
    private LogManager $logManager;
    private CookieUtil $cookieUtil;
    private SessionUtil $sessionUtil;
    private UserManager $userManager;
    private EmailManager $emailManager;
    private ErrorManager $errorManager;
    private SecurityUtil $securityUtil;
    private VisitorInfoUtil $visitorInfoUtil;
    private EntityManagerInterface $entityManager;

    public function __construct(
        AppUtil $appUtil,
        CacheUtil $cacheUtil,
        LogManager $logManager,
        CookieUtil $cookieUtil,
        SessionUtil $sessionUtil,
        UserManager $userManager,
        EmailManager $emailManager,
        ErrorManager $errorManager,
        SecurityUtil $securityUtil,
        VisitorInfoUtil $visitorInfoUtil,
        EntityManagerInterface $entityManager
    ) {
        $this->appUtil = $appUtil;
        $this->cacheUtil = $cacheUtil;
        $this->logManager = $logManager;
        $this->cookieUtil = $cookieUtil;
        $this->sessionUtil = $sessionUtil;
        $this->userManager = $userManager;
        $this->emailManager = $emailManager;
        $this->errorManager = $errorManager;
        $this->securityUtil = $securityUtil;
        $this->entityManager = $entityManager;
        $this->visitorInfoUtil = $visitorInfoUtil;
    }

    /**
     * Check is username is blocked
     *
     * @param string $username The username to check
     *
     * @return bool True if the username is blocked, false otherwise
     */
    public function isUsernameBlocked(string $username): bool
    {
        // get blocked usernames array from config file
        $blockedUsernames = $this->appUtil->loadConfig('blocked-usernames.json');

        // check is blocked usernames array found
        if ($blockedUsernames == null) {
            return false;
        }

        // check is username is blocked
        $result = in_array($username, $blockedUsernames);

        return $result;
    }

    /**
     * Register new user to database
     *
     * @param string $username The username of the new user
     * @param string $password The password of the new user
     *
     * @throws Exception New user flush to database failed
     *
     * @return void
     */
    public function registerUser(string $username, string $password): void
    {
        // check is username is blocked
        if ($this->isUsernameBlocked($username)) {
            $this->errorManager->handleError(
                'error to register new user: username is system',
                Response::HTTP_FORBIDDEN
            );
        }

        // check if user already exist in database
        if ($this->userManager->checkIfUserExist($username)) {
            $this->errorManager->handleError(
                'error to register new user: username already exist',
                Response::HTTP_FORBIDDEN
            );
        }

        // generate user auth token
        $token = $this->generateUserToken();

        // hash user password
        $password = $this->securityUtil->generateHash($password);

        // get current time
        $time = new DateTime();

        // get user ip address
        $ip_address = $this->visitorInfoUtil->getIP();

        // get user browser agent
        $user_agent = $this->visitorInfoUtil->getUserAgent();

        // check if ip address is unknown
        if ($ip_address == null) {
            $ip_address = 'Unknown';
        }

        // check if user agent is unknown
        if ($user_agent == null) {
            $user_agent = 'Unknown';
        }

        // init user entity
        $user = new User();

        // set new user properties
        $user->setUsername($username)
            ->setPassword($password)
            ->setRole('USER')
            ->setIpAddress($ip_address)
            ->setUserAgent($user_agent)
            ->setToken($token)
            ->setProfilePic('default_pic')
            ->setRegisterTime($time)
            ->setLastLoginTime($time);

        try {
            // persist and flush user to database
            $this->entityManager->persist($user);
            $this->entityManager->flush();

            // log user registration event
            $this->logManager->log(
                name: 'authenticator',
                message: 'new registration user: ' . $username,
                level: LogManager::LEVEL_CRITICAL
            );
        } catch (Exception $e) {
            $this->errorManager->handleError(
                message: 'error to register new user: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Get current logged user repository
     *
     * @return User|null The user object if found, null otherwise
     */
    public function getLoggedUserRepository(): ?User
    {
        // check is user logged in
        if (!$this->isUserLogedin()) {
            return null;
        }

        // get logged user token from session
        $token = $this->sessionUtil->getSessionValue('user-token');

        // check if token is string
        if (!is_string($token)) {
            $this->errorManager->handleError(
                message: 'error to get logged user token: token is not a string',
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        // get user repository by auth token
        return $this->userManager->getUserByToken($token);
    }

    /**
     * Check is current logged user is admin
     *
     * @return bool The is user admin or not
     */
    public function isLoggedInUserAdmin(): bool
    {
        // check is user logged in
        if (!$this->isUserLogedin()) {
            return false;
        }

        // get logged user repository
        $user = $this->getLoggedUserRepository();

        // check is user exist
        if ($user == null) {
            return false;
        }

        // check is user is admin
        if ($this->userManager->isUserAdmin((int) $user->getId())) {
            return true;
        }

        return false;
    }

    /**
     * Check is user logged in
     *
     * @return bool The user is logged in or not
     */
    public function isUserLogedin(): bool
    {
        // check if session exist
        if (!$this->sessionUtil->checkSession('user-token')) {
            return false;
        }

        // get login auth token form session
        $loginToken = $this->sessionUtil->getSessionValue('user-token');

        // check is login token is string
        if (!is_string($loginToken)) {
            $this->errorManager->handleError(
                message: 'error to check if user is logged in: login token is not a string',
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        // check is user token exist in database
        if ($this->userManager->getUserByToken($loginToken) != null) {
            return true;
        }

        return false;
    }

    /**
     * Check if a user can login
     *
     * @param string $username The username of the user
     * @param string $password The password of the user
     *
     * @return bool True if user can login, otherwise false
     */
    public function canLogin(string $username, string $password): bool
    {
        // get user repository
        $user = $this->userManager->getUserByUsername($username);

        // check if user exist
        if ($user != null) {
            // check if password is correct
            if ($this->securityUtil->verifyPassword($password, (string) $user->getPassword())) {
                return true;
            }
        }

        // log invalid credentials
        $this->logManager->log(
            name: 'authenticator',
            message: 'invalid login user: ' . $username . ':' . $password,
            level: LogManager::LEVEL_CRITICAL
        );

        return false;
    }

    /**
     * Login user to the system
     *
     * @param string $username The username of the user
     * @param bool $remember Whether to remember the user
     *
     * @throws Exception Login process error
     *
     * @return void
     */
    public function login(string $username, bool $remember): void
    {
        // get user repository
        $user = $this->userManager->getUserByUsername($username);

        // check if user exist
        if ($user != null) {
            // get user auth token
            $token = (string) $user->getToken();

            try {
                // set user auth token to session storage
                $this->sessionUtil->setSession('user-token', $token);

                // save user identifier in session
                $this->sessionUtil->setSession('user-identifier', (string) $user->getId());

                // set user token cookie for auto login on browser restart
                if ($remember) {
                    $this->cookieUtil->set('user-token', $token, time() + (60 * 60 * 24 * 7 * 365));
                }

                // update user login time and ip address
                $this->updateDataOnLogin($token);

                // log user login event
                $this->logManager->log(
                    name: 'authenticator',
                    message: 'login user: ' . $username,
                    level: LogManager::LEVEL_CRITICAL
                );

                // send email alert
                if (!$this->logManager->isAntiLogEnabled()) {
                    $this->emailManager->sendDefaultEmail(
                        recipient: $this->appUtil->getEnvValue('ADMIN_CONTACT'),
                        subject: 'LOGIN ALERT',
                        message: 'User ' . $username . ' has logged to admin-suite dashboard, login log has been saved in database.'
                    );
                }
            } catch (Exception $e) {
                $this->errorManager->handleError(
                    message: 'error to login user: ' . $e->getMessage(),
                    code: Response::HTTP_INTERNAL_SERVER_ERROR
                );
            }
        }
    }

    /**
     * Update user data on login
     *
     * @param string $token The user token
     *
     * @throws Exception User data update flush to database failed
     *
     * @return void
     */
    public function updateDataOnLogin(string $token): void
    {
        // get user repository
        $user = $this->userManager->getUserByToken($token);

        // check if user found
        if ($user == null) {
            $this->errorManager->handleError(
                message: 'error to update user data: user not found',
                code: Response::HTTP_NOT_FOUND
            );
        }

        // update user data
        $user->setLastLoginTime(new DateTime())
            ->setIpAddress($this->visitorInfoUtil->getIP() ?? 'Unknown')
            ->setUserAgent($this->visitorInfoUtil->getUserAgent() ?? 'Unknown');

        try {
            // flush updated user data
            $this->entityManager->flush();
        } catch (Exception $e) {
            $this->errorManager->handleError(
                message: 'error to update user data: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Get current logged user id
     *
     * @return int The id of logged in user
     */
    public function getLoggedUserId(): int
    {
        $userId = 0;

        // check if user is logged in
        if ($this->isUserLogedin()) {
            // get user auth token
            $token = $this->getLoggedUserToken();

            // check if token is string
            if (!is_string($token)) {
                $this->errorManager->handleError(
                    message: 'error to get logged user id: token is not a string',
                    code: Response::HTTP_INTERNAL_SERVER_ERROR
                );
            }

            // get user by token
            $user = $this->userManager->getUserByToken($token);

            // check if user exist
            if ($user != null) {
                // get user id
                $userId = (int) $user->getId();
            }
        }

        return $userId;
    }

    /**
     * Get current logged user token
     *
     * @return string|null The login token or null if not found or invalid
     */
    public function getLoggedUserToken(): string|null
    {
        // check if session exist
        if (!$this->sessionUtil->checkSession('user-token')) {
            return null;
        }

        // get auth token from session storage
        $loginToken = $this->sessionUtil->getSessionValue('user-token');

        // check if token is string
        if (!is_string($loginToken)) {
            $this->errorManager->handleError(
                message: 'error to get logged user token: login token is not a string',
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        // check if token exist in database
        if ($this->userManager->getUserByToken($loginToken) != null) {
            return $loginToken;
        }

        return null;
    }

    /**
     * Get current logged user username
     *
     * @return string|null The username of the logged user or null if not found or invalid
     */
    public function getLoggedUsername(): ?string
    {
        // get current logged user token
        $token = $this->getLoggedUserToken();

        // check if token is string
        if (!is_string($token)) {
            $this->errorManager->handleError(
                message: 'error to get logged user token: token is not a string',
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        // get user repository by auth token
        $user = $this->userManager->getUserByToken($token);

        // check if user exist
        if ($user == null) {
            $this->errorManager->handleError(
                message: 'error to get logged user username',
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        // get user username
        $username = $user->getUsername();

        return $username;
    }

    /**
     * Logout user action
     *
     * @return void
     */
    public function logout(): void
    {
        // check is user logged in
        if ($this->isUserLogedin()) {
            // get logged user token
            $token = $this->getLoggedUserToken();

            // check if token is string
            if (!is_string($token)) {
                $this->errorManager->handleError(
                    message: 'error to logout user: token is not a string',
                    code: Response::HTTP_INTERNAL_SERVER_ERROR
                );
            }

            // get user repository by auth token
            $user = $this->userManager->getUserByToken($token);

            // check if user found
            if ($user == null) {
                $this->errorManager->handleError(
                    message: 'error to update user data: user not found',
                    code: Response::HTTP_NOT_FOUND
                );
            }

            // log user logout event
            $this->logManager->log(
                name: 'authenticator',
                message: 'logout user: ' . $user->getUsername(),
                level: LogManager::LEVEL_CRITICAL
            );

            // unset login cookie
            $this->cookieUtil->unset('user-token');

            // unset login session
            $this->sessionUtil->destroySession();
        }
    }

    /**
     * Reset the user password
     *
     * @param string $username The username to password reset
     *
     * @throws Exception Password reset process error
     *
     * @return string|null The new password or null on error
     */
    public function resetUserPassword(string $username): ?string
    {
        /** @var \App\Entity\User $user */
        $user = $this->userManager->getUserByUsername($username);

        // check if user exist
        if ($user != null) {
            try {
                // generate new user password
                $newPassword = ByteString::fromRandom(32)->toString();

                // hash new user password
                $newPasswordHash = $this->securityUtil->generateHash($newPassword);

                // genetate new auth token
                $newToken = $this->generateUserToken();

                // set new password and auth token
                $user->setPassword($newPasswordHash);
                $user->setToken($newToken);

                // flush update to database
                $this->entityManager->flush();

                // log password reset event
                $this->logManager->log(
                    name: 'authenticator',
                    message: 'user: ' . $username . ' password reset is success',
                    level: LogManager::LEVEL_CRITICAL
                );
            } catch (Exception $e) {
                $this->errorManager->handleError(
                    message: 'error to reset user password: ' . $e->getMessage(),
                    code: Response::HTTP_INTERNAL_SERVER_ERROR
                );
            }

            // return new user password
            return $newPassword;
        }

        // default return (non existing user)
        return null;
    }

    /**
     * Regenerate tokens for all users in the database
     *
     * @throws Exception Error to flush new user tokens to database
     *
     * @return array<bool|null|string> An array containing the status of operation
     * - The 'status' key indicates if the operation was successful (true) or not (false)
     * - The 'message' key contains any relevant error message if the operation failed, otherwise it is null
     */
    public function regenerateUsersTokens(): array
    {
        $state = [
            'status' => true,
            'message' => null
        ];

        /** @var \App\Entity\User[] $userRepositories */
        $userRepositories = $this->userManager->getAllUsersRepositories();

        // regenerate all users tokens
        foreach ($userRepositories as $user) {
            // generate new auth token
            $newToken = $this->generateUserToken();

            // set new user token
            $user->setToken($newToken);
        }

        try {
            // flush changes to database
            $this->entityManager->flush();
        } catch (Exception $e) {
            $state = [
                'status' => false,
                'message' => $e->getMessage()
            ];
        }

        // log regenerate all users tokens event
        $this->logManager->log(
            name: 'authenticator',
            message: 'regenerate all users tokens',
            level: LogManager::LEVEL_WARNING
        );

        // return process status output
        return $state;
    }

    /**
     * Generate aunique 32 characters length auth token for a user
     *
     * @return string The generated user token
     */
    public function generateUserToken(): string
    {
        do {
            // generate user token
            $token = ByteString::fromRandom(32)->toString();
        } while ($this->userManager->getUserByToken($token) != null);

        return $token;
    }

    /**
     * Store online user id in cache
     *
     * @param int $userId The id of user to store
     *
     * @return void
     */
    public function cacheOnlineUser(int $userId): void
    {
        // cache online visitor
        $this->cacheUtil->setValue('online_user_' . $userId, 'online', 300);
    }

    /**
     * Get online users list
     *
     * @return array<mixed> The list of online users
     */
    public function getOnlineUsersList(): array
    {
        $onlineVisitors = [];

        /** @var \App\Entity\User[] $users */
        $users = $this->userManager->getAllUsersRepositories();

        // check if $users is iterable
        if (!is_iterable($users)) {
            return $onlineVisitors;
        }

        // check all users status
        foreach ($users as $user) {
            $userId = $user->getId();

            // check if id is not null
            if ($userId != null) {
                // get visitor status
                $status = $this->getUserStatus($userId);

                // check visitor status
                if ($status == 'online') {
                    array_push($onlineVisitors, $user);
                }
            }
        }

        return $onlineVisitors;
    }

    /**
     * Get user online status
     *
     * @param int $userId The id of the user
     *
     * @return string The user online status
     */
    public function getUserStatus(int $userId): string
    {
        $userCacheKey = 'online_user_' . $userId;

        // get user status form cache
        $cacheItem = $this->cacheUtil->getValue($userCacheKey);

        // check if item is cache item object
        if ($cacheItem instanceof CacheItemInterface) {
            // get value from cache item
            $status = $cacheItem->get();

            // check if status found
            if (is_string($status) && $status !== null && $status !== '') {
                return $status;
            }
        }

        return 'offline';
    }
}
