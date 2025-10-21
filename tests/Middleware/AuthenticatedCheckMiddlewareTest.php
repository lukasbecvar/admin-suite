<?php

namespace App\Tests\Middleware;

use App\Manager\AuthManager;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Request;
use PHPUnit\Framework\Attributes\CoversClass;
use App\Middleware\AuthenticatedCheckMiddleware;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Class AuthenticatedCheckMiddlewareTest
 *
 * Test cases for authenticated check middleware
 *
 * @package App\Tests\Middleware
 */
#[CoversClass(AuthenticatedCheckMiddleware::class)]
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

        // create middleware instance
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
     * Test request when user is logged in
     *
     * @return void
     */
    public function testRequestWhenUserIsLoggedIn(): void
    {
        // create testing request event
        $event = $this->createRequestEvent('/admin');

        // simulate user is logged in
        $this->authManagerMock->expects($this->once())->method('isUserLogedin')->willReturn(true);

        // call tested middleware
        $this->middleware->onKernelRequest($event);

        // assert response
        $this->assertNull($event->getResponse());
    }

    /**
     * Test request to admin page when user is not logged in
     *
     * @return void
     */
    public function testRequestToAdminPageWhenUserIsNotLoggedIn(): void
    {
        // create testing request event
        $event = $this->createRequestEvent('/dashboard');

        // simulate user is not logged in
        $this->authManagerMock->expects($this->once())->method('isUserLogedin')->willReturn(false);

        // expect call url generator
        $this->urlGeneratorMock->expects($this->once())
            ->method('generate')->with('app_auth_login')->willReturn('/login');

        // call tested middleware
        $this->middleware->onKernelRequest($event);

        // assert response
        $this->assertInstanceOf(RedirectResponse::class, $event->getResponse());
    }

    /**
     * Test request when pages are excluded from authentication check
     *
     * @return void
     */
    public function testRequestsForExcludedPages(): void
    {
        // list of excluded paths
        $excludedPaths = [
            '/login',
            '/register',
            '/',
            '/error',
            '/_profiler'
        ];

        // expect auth manager not called
        $this->authManagerMock->expects($this->never())->method('isUserLogedin');

        // test each excluded path
        foreach ($excludedPaths as $path) {
            // create testing request event
            $event = $this->createRequestEvent($path);

            // call tested middleware
            $this->middleware->onKernelRequest($event);

            // assert response
            $this->assertNull($event->getResponse(), "Failed asserting for excluded path: $path");
        }
    }
}
