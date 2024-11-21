<?php

namespace App\Tests\Middleware;

use App\Manager\AuthManager;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Request;
use App\Middleware\AuthenticatedCheckMiddleware;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Class AuthenticatedCheckMiddlewareTest
 *
 * Test the authentificated middleware
 *
 * @package App\Tests\Middleware
 */
class AuthenticatedCheckMiddlewareTest extends TestCase
{
    private AuthManager & MockObject $authManagerMock;
    private AuthenticatedCheckMiddleware $middleware;
    private UrlGeneratorInterface & MockObject $urlGeneratorMock;

    protected function setUp(): void
    {
        // mock dependencies
        $this->authManagerMock = $this->createMock(AuthManager::class);
        $this->urlGeneratorMock = $this->createMock(UrlGeneratorInterface::class);

        // create the middleware instance
        $this->middleware = new AuthenticatedCheckMiddleware(
            $this->authManagerMock,
            $this->urlGeneratorMock
        );
    }

    /**
     * Create request event
     *
     * @param string $pathInfo
     *
     * @return RequestEvent
     */
    private function createRequestEvent(string $pathInfo): RequestEvent
    {
        /** @var HttpKernelInterface&MockObject $kernel */
        $kernel = $this->createMock(HttpKernelInterface::class);
        $request = new Request([], [], [], [], [], ['REQUEST_URI' => $pathInfo]);
        return new RequestEvent($kernel, $request, HttpKernelInterface::MAIN_REQUEST);
    }

    /**
     * Test user already logged in user
     *
     * @return void
     */
    public function testRequestUserAlreadyLoggedIn(): void
    {
        // mock the auth manager
        $this->authManagerMock->expects($this->once())->method('isUserLogedin')->willReturn(true);

        // mock the url generator
        $event = $this->createRequestEvent('/admin');

        // call middleware tested method
        $this->middleware->onKernelRequest($event);

        // assert the result
        $this->assertNull($event->getResponse());
    }

    /**
     * Test login page request
     *
     * @return void
     */
    public function testRequestLoginPage(): void
    {
        // mock the auth manager
        $this->authManagerMock->expects($this->never())->method('isUserLogedin');

        // create request event
        $event = $this->createRequestEvent('/login');

        // call middleware tested method
        $this->middleware->onKernelRequest($event);

        // assert the result
        $this->assertNull($event->getResponse());
    }

    /**
     * Test register page request
     *
     * @return void
     */
    public function testRequestRegisterPage(): void
    {
        // mock the auth manager
        $this->authManagerMock->expects($this->never())->method('isUserLogedin');

        // create request event
        $event = $this->createRequestEvent('/register');

        // call middleware tested method
        $this->middleware->onKernelRequest($event);

        // assert the result
        $this->assertNull($event->getResponse());
    }

    /**
     * Test index component request
     *
     * @return void
     */
    public function testRequestRootPage(): void
    {
        // mock the auth manager
        $this->authManagerMock->expects($this->never())->method('isUserLogedin');

        // create request event
        $event = $this->createRequestEvent('/');

        // call middleware tested method
        $this->middleware->onKernelRequest($event);

        // assert the result
        $this->assertNull($event->getResponse());
    }

    /**
     * Test error page request
     *
     * @return void
     */
    public function testRequestErrorPage(): void
    {
        // mock the auth manager
        $this->authManagerMock->expects($this->never())->method('isUserLogedin');

        // create request event
        $event = $this->createRequestEvent('/error');

        // call middleware tested method
        $this->middleware->onKernelRequest($event);

        // assert the result
        $this->assertNull($event->getResponse());
    }

    /**
     * Test profiler page request
     *
     * @return void
     */
    public function testRequestProfilerPage(): void
    {
        // mock the auth manager
        $this->authManagerMock->expects($this->never())->method('isUserLogedin');

        // create request event
        $event = $this->createRequestEvent('/_profiler');

        // call middleware tested method
        $this->middleware->onKernelRequest($event);

        // assert the result
        $this->assertNull($event->getResponse());
    }

    /**
     * Test admin page (dashboard) request
     *
     * @return void
     */
    public function testRequestRedirectToLogin(): void
    {
        // mock the auth manager
        $this->authManagerMock->expects($this->once())->method('isUserLogedin')->willReturn(false);

        // mock the url generator
        $this->urlGeneratorMock->expects($this->once())
            ->method('generate')->with('app_auth_login')->willReturn('/login');

        // create request event
        $event = $this->createRequestEvent('/dashboard');

        // call middleware tested method
        $this->middleware->onKernelRequest($event);

        // assert the result
        $response = $event->getResponse();

        // assert the result
        $this->assertInstanceOf(RedirectResponse::class, $response);
    }
}
