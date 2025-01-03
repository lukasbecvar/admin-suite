<?php

namespace App\Tests\Middleware;

use App\Entity\User;
use Twig\Environment;
use App\Manager\AuthManager;
use PHPUnit\Framework\TestCase;
use App\Middleware\AuthorizationMiddleware;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * Class AuthorizationMiddlewareTest
 *
 * Tests for the authorization middleware
 *
 * @package App\Tests\Middleware
 */
class AuthorizationMiddlewareTest extends TestCase
{
    private Environment & MockObject $twig;
    private AuthManager & MockObject $authManager;
    private AuthorizationMiddleware $authorizationMiddleware;

    protected function setUp(): void
    {
        // mock dependencies
        $this->twig = $this->createMock(Environment::class);
        $this->authManager = $this->createMock(AuthManager::class);

        // create the middleware instance
        $this->authorizationMiddleware = new AuthorizationMiddleware($this->twig, $this->authManager);
    }

    /**
     * test that a non-admin user is forbidden
     *
     * @return void
     */
    public function testNonAdminUserIsForbidden(): void
    {
        // setup request and event
        $request = new Request();
        $request->attributes->set('_controller', 'App\Controller\AntiLogController::enableAntiLog');

        // mock request event
        /** @var RequestEvent&MockObject $event */
        $event = $this->createMock(RequestEvent::class);
        $event->method('getRequest')->willReturn($request);
        $event->expects($this->once())->method('setResponse')->with($this->callback(function ($response) {
            $this->assertInstanceOf(Response::class, $response);
            $this->assertEquals(Response::HTTP_FORBIDDEN, $response->getStatusCode());
            $this->assertEquals('Forbidden content', $response->getContent());
            return true;
        }));

        // mock AuthManager responses
        $this->authManager->method('isLoggedInUserAdmin')->willReturn(false);

        $mockUser = $this->createMock(User::class);
        $this->authManager->method('getLoggedUserRepository')->willReturn($mockUser);

        // mock Twig response
        $this->twig->method('render')->willReturn('Forbidden content');

        // call middleware tested method
        $this->authorizationMiddleware->onKernelRequest($event);
    }

    /**
     * test that an admin user is allowed
     *
     * @return void
     */
    public function testAdminUserIsAllowed(): void
    {
        // setup request and event
        $request = new Request();
        $request->attributes->set('_controller', 'App\Controller\AntiLogController::enableAntiLog');

        // mock request event
        /** @var RequestEvent&MockObject $event */
        $event = $this->createMock(RequestEvent::class);
        $event->method('getRequest')->willReturn($request);

        // mock AuthManager responses
        $this->authManager->method('isLoggedInUserAdmin')->willReturn(true);

        // call middleware tested method
        $this->authorizationMiddleware->onKernelRequest($event);

        // assert that no response is set, indicating access is allowed
        $event->expects($this->never())->method('setResponse');
    }

    /**
     * test that no authorization annotation
     *
     * @return void
     */
    public function testNoAuthorizationAnnotationDefaultsToUser(): void
    {
        // setup request and event
        $request = new Request();
        $request->attributes->set('_controller', 'App\Controller\IndexController::index');

        // mock request event
        /** @var RequestEvent&MockObject $event */
        $event = $this->createMock(RequestEvent::class);
        $event->method('getRequest')->willReturn($request);

        // mock AuthManager responses
        $this->authManager->method('isLoggedInUserAdmin')->willReturn(false);

        // call middleware tested method
        $this->authorizationMiddleware->onKernelRequest($event);

        // assert that no response is set, indicating access is allowed
        $event->expects($this->never())->method('setResponse');
    }
}
