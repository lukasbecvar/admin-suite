<?php

namespace App\Tests\Middleware;

use App\Entity\User;
use App\Util\CookieUtil;
use App\Util\SessionUtil;
use App\Manager\AuthManager;
use App\Manager\UserManager;
use PHPUnit\Framework\TestCase;
use App\Middleware\AutoLoginMiddleware;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Class AutoLoginMiddlewareTest
 *
 * Test cases for auto login middleware
 *
 * @package App\Tests\Middleware
 */
#[CoversClass(AutoLoginMiddleware::class)]
class AutoLoginMiddlewareTest extends TestCase
{
    private AutoLoginMiddleware $middleware;
    private CookieUtil & MockObject $cookieUtilMock;
    private SessionUtil & MockObject $sessionUtilMock;
    private AuthManager & MockObject $authManagerMock;
    private UserManager & MockObject $userManagerMock;

    protected function setUp(): void
    {
        // mock dependencies
        $this->cookieUtilMock = $this->createMock(CookieUtil::class);
        $this->sessionUtilMock = $this->createMock(SessionUtil::class);
        $this->authManagerMock = $this->createMock(AuthManager::class);
        $this->userManagerMock = $this->createMock(UserManager::class);

        // create middleware instance
        $this->middleware = new AutoLoginMiddleware(
            $this->cookieUtilMock,
            $this->sessionUtilMock,
            $this->authManagerMock,
            $this->userManagerMock
        );
    }

    /**
     * Test request when user is already logged in
     *
     * @return void
     */
    public function testRequestWhenUserIsAlreadyLoggedIn(): void
    {
        // simulate user is logged in
        $this->authManagerMock->expects($this->once())->method('isUserLogedin')->willReturn(true);

        // expect cookie token get not called
        $this->cookieUtilMock->expects($this->never())->method('get');

        // call tested middleware
        $this->middleware->onKernelRequest();
    }

    /**
     * Test request when cookie token is not set
     *
     * @return void
     */
    public function testRequestWhenCookieTokenIsNotSet(): void
    {
        // simulate user is not logged in
        $this->authManagerMock->expects($this->once())->method('isUserLogedin')->willReturn(false);

        // unser cookie token
        unset($_COOKIE['user-token']);

        // expect cookie token get not called
        $this->cookieUtilMock->expects($this->never())->method('get');

        // call tested middleware
        $this->middleware->onKernelRequest();
    }

    /**
     * Test request when token is valid
     *
     * @return void
     */
    public function testRequestWhenTokenIsValid(): void
    {
        // mock user entity
        $userToken = 'valid_token';
        $user = new User();
        $user->setUsername('testuser');

        // simulate user token found in cookie
        $this->cookieUtilMock->method('isCookieSet')->with('user-token')->willReturn(true);

        // simulate user is not logged in
        $this->authManagerMock->expects($this->once())->method('isUserLogedin')->willReturn(false);

        // simulate get token from cookie
        $this->cookieUtilMock->expects($this->once())->method('get')->with('user-token')->willReturn($userToken);

        // mock user manager
        $this->userManagerMock->expects($this->exactly(2))
            ->method('getUserRepository')->with(['token' => $userToken])->willReturn($user);

        // expect call login
        $this->authManagerMock->expects($this->once())->method('login')->with('testuser', true);

        // call tested middleware
        $this->middleware->onKernelRequest();
    }

    /**
     * Test request when token is invalid
     *
     * @return void
     */
    public function testRequestWhenTokenIsInvalid(): void
    {
        // invalid token
        $userToken = 'invalid_token';

        // simulate user token found in cookie
        $this->cookieUtilMock->method('isCookieSet')->with('user-token')->willReturn(true);

        // simulate user is not logged in
        $this->authManagerMock->expects($this->once())->method('isUserLogedin')->willReturn(false);

        // simulate get token from cookie
        $this->cookieUtilMock->expects($this->once())->method('get')->with('user-token')->willReturn($userToken);

        // simulate user with token not found in database
        $this->userManagerMock->expects($this->once())->method('getUserRepository')->with(['token' => $userToken])->willReturn(null);

        // expect cookie token unset (logout invalid session)
        $this->cookieUtilMock->expects($this->once())->method('unset')->with('user-token');

        // expect call session destroy (logout invalid session)
        $this->sessionUtilMock->expects($this->once())->method('destroySession');

        // call tested middleware
        $this->middleware->onKernelRequest();
    }
}
