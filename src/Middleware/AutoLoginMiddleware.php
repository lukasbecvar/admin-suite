<?php

namespace App\Middleware;

use App\Entity\User;
use App\Util\CookieUtil;
use App\Util\SessionUtil;
use App\Manager\AuthManager;
use App\Manager\UserManager;

/**
 * Class AutoLoginMiddleware
 *
 * This middleware checks if the required auto-login function should be triggered.
 *
 * @package App\Middleware
 */
class AutoLoginMiddleware
{
    private CookieUtil $cookieUtil;
    private SessionUtil $sessionUtil;
    private AuthManager $authManager;
    private UserManager $userManager;

    public function __construct(CookieUtil $cookieUtil, SessionUtil $sessionUtil, AuthManager $authManager, UserManager $userManager)
    {
        $this->cookieUtil = $cookieUtil;
        $this->sessionUtil = $sessionUtil;
        $this->authManager = $authManager;
        $this->userManager = $userManager;
    }

    /**
     * Handle auto-login process for remember me feature.
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
                // init user entity
                $user = new User();

                // get user token
                $userToken = $this->cookieUtil->get('user-token');

                // check if token exist in database
                if ($this->userManager->getUserRepository(['token' => $userToken]) != null) {
                    // get user data
                    $user = $this->userManager->getUserRepository(['token' => $userToken]);

                    // get username to login
                    $username = $user->getUsername();

                    // auto login user
                    $this->authManager->login((string) $username, true);
                } else {
                    $this->cookieUtil->unset('user-token');

                    // destory session is cookie token is invalid
                    $this->sessionUtil->destroySession();
                }
            }
        }

        // check if user logged in
        if ($loginStatus) {
            // cache user online status
            $this->authManager->cacheOnlineUser($this->authManager->getLoggedUserId());
        }
    }
}
