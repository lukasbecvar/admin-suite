<?php

namespace App\Tests\Middleware;

use App\Manager\AuthManager;
use App\Middleware\AuthentificatedCheckMiddleware;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

class AuthentificatedCheckMiddlewareTest extends TestCase
{
    /** @var AuthManager|MockObject */
    private AuthManager|MockObject $authManagerMock;

    /** @var UrlGeneratorInterface|MockObject */
    private UrlGeneratorInterface|MockObject $urlGeneratorMock;

    /** @var AuthentificatedCheckMiddleware */
    private AuthentificatedCheckMiddleware $middleware;

    protected function setUp(): void
    {
        $this->authManagerMock = $this->createMock(AuthManager::class);
        $this->urlGeneratorMock = $this->createMock(UrlGeneratorInterface::class);

        $this->middleware = new AuthentificatedCheckMiddleware(
            $this->authManagerMock,
            $this->urlGeneratorMock
        );
    }

    private function createRequestEvent(string $pathInfo): RequestEvent
    {
        $request = new Request([], [], [], [], [], ['REQUEST_URI' => $pathInfo]);
        $kernel = $this->createMock(HttpKernelInterface::class);
        return new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);
    }

    public function testOnKernelRequestUserAlreadyLoggedIn(): void
    {
        $this->authManagerMock->expects($this->once())
            ->method('isUserLogedin')
            ->willReturn(true);

        $event = $this->createRequestEvent('/admin');

        $this->middleware->onKernelRequest($event);

        $this->assertNull($event->getResponse());
    }

    public function testOnKernelRequestLoginPage(): void
    {
        $this->authManagerMock->expects($this->never())
            ->method('isUserLogedin');

        $event = $this->createRequestEvent('/login');

        $this->middleware->onKernelRequest($event);

        $this->assertNull($event->getResponse());
    }

    public function testOnKernelRequestRegisterPage(): void
    {
        $this->authManagerMock->expects($this->never())
            ->method('isUserLogedin');

        $event = $this->createRequestEvent('/register');

        $this->middleware->onKernelRequest($event);

        $this->assertNull($event->getResponse());
    }

    public function testOnKernelRequestRootPage(): void
    {
        $this->authManagerMock->expects($this->never())
            ->method('isUserLogedin');

        $event = $this->createRequestEvent('/');

        $this->middleware->onKernelRequest($event);

        $this->assertNull($event->getResponse());
    }

    public function testOnKernelRequestErrorPage(): void
    {
        $this->authManagerMock->expects($this->never())
            ->method('isUserLogedin');

        $event = $this->createRequestEvent('/error');

        $this->middleware->onKernelRequest($event);

        $this->assertNull($event->getResponse());
    }

    public function testOnKernelRequestProfilerPage(): void
    {
        $this->authManagerMock->expects($this->never())
            ->method('isUserLogedin');

        $event = $this->createRequestEvent('/_profiler');

        $this->middleware->onKernelRequest($event);

        $this->assertNull($event->getResponse());
    }

    public function testOnKernelRequestRedirectToLogin(): void
    {
        $this->authManagerMock->expects($this->once())
            ->method('isUserLogedin')
            ->willReturn(false);

        $loginUrl = '/login';
        $this->urlGeneratorMock->expects($this->once())
            ->method('generate')
            ->with('app_auth_login')
            ->willReturn($loginUrl);

        $event = $this->createRequestEvent('/dashboard');

        $this->middleware->onKernelRequest($event);

        $response = $event->getResponse();
        $this->assertInstanceOf(RedirectResponse::class, $response);
    }
}
