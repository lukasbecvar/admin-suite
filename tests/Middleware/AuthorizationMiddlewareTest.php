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
 * Test cases for authorization middleware
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

        // create middleware instance
        $this->authorizationMiddleware = new AuthorizationMiddleware($this->twig, $this->authManager);
    }

    /**
     * Test request when user is not authorized
     *
     * @return void
     */
    public function testRequestWhenUserIsNotAuthorized(): void
    {
        // setup request and event
        $request = new Request();
        $request->attributes->set('_controller', 'App\Controller\AntiLogController::enableAntiLog');
        /** @var RequestEvent&MockObject $event */
        $event = $this->createMock(RequestEvent::class);
        $event->method('getRequest')->willReturn($request);

        // mock user
        $mockUser = $this->createMock(User::class);
        $this->authManager->method('isLoggedInUserAdmin')->willReturn(false);
        $this->authManager->method('getLoggedUserRepository')->willReturn($mockUser);

        // expect middleware response
        $event->expects($this->once())->method('setResponse')->with($this->callback(function ($response) {
            $this->assertInstanceOf(Response::class, $response);
            $this->assertEquals(Response::HTTP_FORBIDDEN, $response->getStatusCode());
            $this->assertEquals('Forbidden content', $response->getContent());
            return true;
        }));

        // expect call twig render
        $this->twig->expects($this->once())->method('render')->with('component/no-permissions.twig')
            ->willReturn('Forbidden content');

        // call tested middleware
        $this->authorizationMiddleware->onKernelRequest($event);
    }

    /**
     * Test request when user is authorized
     *
     * @return void
     */
    public function testRequestWhenUserIsAuthorized(): void
    {
        // setup request and event
        $request = new Request();
        $request->attributes->set('_controller', 'App\Controller\AntiLogController::enableAntiLog');

        // mock request event
        /** @var RequestEvent&MockObject $event */
        $event = $this->createMock(RequestEvent::class);
        $event->method('getRequest')->willReturn($request);

        // simulate user is admin
        $this->authManager->method('isLoggedInUserAdmin')->willReturn(true);

        // call tested middleware
        $this->authorizationMiddleware->onKernelRequest($event);

        // expect response not set
        $event->expects($this->never())->method('setResponse');
    }

    /**
     * Test request to controller without authorization annotation
     *
     * @return void
     */
    public function testRequestToControllerWithoutAuthorizationAnnotation(): void
    {
        // setup request and event
        $request = new Request();
        $request->attributes->set('_controller', 'App\Controller\IndexController::index');

        // mock request event
        /** @var RequestEvent&MockObject $event */
        $event = $this->createMock(RequestEvent::class);
        $event->method('getRequest')->willReturn($request);

        // simulate user is not admin
        $this->authManager->method('isLoggedInUserAdmin')->willReturn(false);

        // call tested middleware
        $this->authorizationMiddleware->onKernelRequest($event);

        // expect response not set
        $event->expects($this->never())->method('setResponse');
    }
}
