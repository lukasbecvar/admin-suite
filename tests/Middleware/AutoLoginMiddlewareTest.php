<?php

namespace App\Tests\Middleware;

use App\Entity\User;
use App\Manager\AuthManager;
use App\Manager\ErrorManager;
use App\Manager\UserManager;
use App\Middleware\AutoLoginMiddleware;
use App\Util\CookieUtil;
use App\Util\SessionUtil;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

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

    public function testOnKernelRequestUserAlreadyLoggedIn(): void
    {
        $this->authManagerMock->expects($this->once())
            ->method('isUserLogedin')
            ->willReturn(true);

        $this->cookieUtilMock->expects($this->never())
            ->method('get');

        $this->middleware->onKernelRequest();
    }

    public function testOnKernelRequestCookieNotSet(): void
    {
        $this->authManagerMock->expects($this->once())
            ->method('isUserLogedin')
            ->willReturn(false);

        unset($_COOKIE['user-token']);

        $this->cookieUtilMock->expects($this->never())
            ->method('get');

        $this->middleware->onKernelRequest();
    }

    public function testOnKernelRequestTokenExists(): void
    {
        $userToken = 'valid_token';
        $user = new User();
        $user->setUsername('testuser');

        $this->authManagerMock->expects($this->once())
            ->method('isUserLogedin')
            ->willReturn(false);

        $_COOKIE['user-token'] = $userToken;

        $this->cookieUtilMock->expects($this->once())
            ->method('get')
            ->with('user-token')
            ->willReturn($userToken);

        $this->userManagerMock->expects($this->exactly(2))
            ->method('getUserRepo')
            ->with(['token' => $userToken])
            ->willReturn($user);

        $this->authManagerMock->expects($this->once())
            ->method('login')
            ->with('testuser', true);

        $this->middleware->onKernelRequest();
    }

    public function testOnKernelRequestInvalidToken(): void
    {
        $userToken = 'invalid_token';

        $this->authManagerMock->expects($this->once())
            ->method('isUserLogedin')
            ->willReturn(false);

        $_COOKIE['user-token'] = $userToken;

        $this->cookieUtilMock->expects($this->once())
            ->method('get')
            ->with('user-token')
            ->willReturn($userToken);

        $this->userManagerMock->expects($this->once())
            ->method('getUserRepo')
            ->with(['token' => $userToken])
            ->willReturn(null);

        $this->cookieUtilMock->expects($this->once())
            ->method('unset')
            ->with('user-token');

        $this->sessionUtilMock->expects($this->once())
            ->method('destroySession');

        $this->middleware->onKernelRequest();
    }
}
