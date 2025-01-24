<?php

namespace App\Middleware;

use App\Util\CookieUtil;
use App\Util\SessionUtil;
use App\Manager\AuthManager;
use App\Manager\UserManager;

/**
 * Class AutoLoginMiddleware
 *
 * Middleware for auto login (remember me) functionality
 *
 * @package App\Middleware
 */
class AutoLoginMiddleware
{
    private CookieUtil $cookieUtil;
    private SessionUtil $sessionUtil;
    private AuthManager $authManager;
    private UserManager $userManager;

    public function __construct(
        CookieUtil $cookieUtil,
        SessionUtil $sessionUtil,
        AuthManager $authManager,
        UserManager $userManager
    ) {
        $this->cookieUtil = $cookieUtil;
        $this->sessionUtil = $sessionUtil;
        $this->authManager = $authManager;
        $this->userManager = $userManager;
    }

    /**
     * Handle auto login process for remember me feature
     *
     * @return void
     */
    public function onKernelRequest(): void
    {
        // get logged in status
        $loginStatus = $this->authManager->isUserLogedin();

        // check if user not logged
        if (!$loginStatus) {
            // check if cookie set
            if ($this->cookieUtil->isCookieSet('user-token')) {
                // get user token from cookie storage
                $userToken = $this->cookieUtil->get('user-token');

                // check if token exist in database
                if ($this->userManager->getUserRepository(['token' => $userToken]) != null) {
                    /** @var \App\Entity\User $user get user data */
                    $user = $this->userManager->getUserRepository(['token' => $userToken]);
                    $username = $user->getUsername() ?? 'Unknown';

                    // login user to the system
                    $this->authManager->login((string) $username, true);
                } else {
                    // destory session and cookie if token not exist in database
                    $this->cookieUtil->unset('user-token');
                    $this->sessionUtil->destroySession();
                }
            }
        }

        // check if user is logged in
        if ($loginStatus) {
            // cache user online status
            $this->authManager->cacheOnlineUser($this->authManager->getLoggedUserId());
        }
    }
}
