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

/**
 * Class AutoLoginMiddlewareTest
 *
 * Test the auto login middleware
 *
 * @package App\Tests\Middleware
 */
class AutoLoginMiddlewareTest extends TestCase
{
    /** @var CookieUtil|MockObject */
    private CookieUtil|MockObject $cookieUtilMock;

    /** @var SessionUtil|MockObject */
    private SessionUtil|MockObject $sessionUtilMock;

    /** @var AuthManager|MockObject */
    private AuthManager|MockObject $authManagerMock;

    /** @var UserManager|MockObject */
    private UserManager|MockObject $userManagerMock;

    /** @var AutoLoginMiddleware */
    private AutoLoginMiddleware $middleware;

    protected function setUp(): void
    {
        $this->cookieUtilMock = $this->createMock(CookieUtil::class);
        $this->sessionUtilMock = $this->createMock(SessionUtil::class);
        $this->authManagerMock = $this->createMock(AuthManager::class);
        $this->userManagerMock = $this->createMock(UserManager::class);

        $this->middleware = new AutoLoginMiddleware(
            $this->cookieUtilMock,
            $this->sessionUtilMock,
            $this->authManagerMock,
            $this->userManagerMock
        );
    }

    /**
     * Test already logged in user
     *
     * @return void
     */
    public function testRequestUserAlreadyLoggedIn(): void
    {
        // mock the auth manager
        $this->authManagerMock->expects($this->once())
            ->method('isUserLogedin')
            ->willReturn(true);

        // mock the url generator
        $this->cookieUtilMock->expects($this->never())
            ->method('get');

        // call the middleware method
        $this->middleware->onKernelRequest();
    }

    /**
     * Test cookie not set
     *
     * @return void
     */
    public function testRequestCookieNotSet(): void
    {
        // mock the auth manager
        $this->authManagerMock->expects($this->once())
            ->method('isUserLogedin')
            ->willReturn(false);

        // unser cookie token
        unset($_COOKIE['user-token']);

        // mock the cookie util
        $this->cookieUtilMock->expects($this->never())
            ->method('get');

        // call the middleware method
        $this->middleware->onKernelRequest();
    }

    /**
     * Test token not exists
     *
     * @return void
     */
    public function testRequestTokenExists(): void
    {
        // mock the user entity
        $userToken = 'valid_token';
        $user = new User();
        $user->setUsername('testuser');

        // mock the cookie util
        $this->cookieUtilMock->method('isCookieSet')
            ->with('user-token')
            ->willReturn(true);

        // mock the auth manager
        $this->authManagerMock->expects($this->once())
            ->method('isUserLogedin')
            ->willReturn(false);

        // mock the cookie util
        $this->cookieUtilMock->expects($this->once())
            ->method('get')
            ->with('user-token')
            ->willReturn($userToken);

        // mock the user manager
        $this->userManagerMock->expects($this->exactly(2))
            ->method('getUserRepository')
            ->with(['token' => $userToken])
            ->willReturn($user);

        // mock the session util
        $this->authManagerMock->expects($this->once())
            ->method('login')
            ->with('testuser', true);

        // call the middleware method
        $this->middleware->onKernelRequest();
    }

    /**
     * Test invalid token
     *
     * @return void
     */
    public function testRequestInvalidToken(): void
    {
        // mock the user entity
        $userToken = 'invalid_token';

        // mock the cookie util
        $this->cookieUtilMock->method('isCookieSet')
            ->with('user-token')
            ->willReturn(true);

        // mock the auth manager
        $this->authManagerMock->expects($this->once())
            ->method('isUserLogedin')
            ->willReturn(false);

        // mock the cookie util
        $this->cookieUtilMock->expects($this->once())
            ->method('get')
            ->with('user-token')
            ->willReturn($userToken);

        // mock the user manager
        $this->userManagerMock->expects($this->once())
            ->method('getUserRepository')
            ->with(['token' => $userToken])
            ->willReturn(null);

        // mock the session util
        $this->cookieUtilMock->expects($this->once())
            ->method('unset')
            ->with('user-token');

        // mock the session util
        $this->sessionUtilMock->expects($this->once())
            ->method('destroySession');

        // call the middleware method
        $this->middleware->onKernelRequest();
    }
}
