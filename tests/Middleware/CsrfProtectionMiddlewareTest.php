<?php

namespace App\Tests\Middleware;

use App\Manager\ErrorManager;
use PHPUnit\Framework\TestCase;
use App\Controller\Auth\LoginController;
use App\Middleware\CsrfProtectionMiddleware;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;

/**
 * Class CsrfProtectionMiddlewareTest
 *
 * Test cases for CSRF protection middleware behaviour
 *
 * @package App\Tests\Middleware
 */
class CsrfProtectionMiddlewareTest extends TestCase
{
    private CsrfProtectionMiddleware $middleware;
    private ErrorManager & MockObject $errorManager;
    private CsrfTokenManagerInterface & MockObject $tokenManager;

    protected function setUp(): void
    {
        $this->errorManager = $this->createMock(ErrorManager::class);
        $this->tokenManager = $this->createMock(CsrfTokenManagerInterface::class);
        $this->middleware = new CsrfProtectionMiddleware($this->errorManager, $this->tokenManager);
    }

    /**
     * Test middleware is ignored for sub-requests
     *
     * @return void
     */
    public function testSkipsNonMainRequest(): void
    {
        $event = $this->createMock(RequestEvent::class);
        $event->expects($this->once())->method('isMainRequest')->willReturn(false);
        $event->expects($this->never())->method('getRequest');

        // call tested middleware
        $this->middleware->onKernelRequest($event);
    }

    /**
     * Test middleware is ignored for get requests
     *
     * @return void
     */
    public function testSkipsNonPostRequests(): void
    {
        $request = Request::create('/test', 'GET');
        $event = $this->createMock(RequestEvent::class);
        $event->method('isMainRequest')->willReturn(true);
        $event->method('getRequest')->willReturn($request);

        // expect token validation not called
        $this->tokenManager->expects($this->never())->method('isTokenValid');

        // call tested middleware
        $this->middleware->onKernelRequest($event);
    }

    /**
     * Test middleware skips controller actions that disabled CSRF
     *
     * @return void
     */
    public function testSkipsWhenAnnotationDisablesProtection(): void
    {
        $request = Request::create('/login', 'POST');
        $request->attributes->set('_controller', LoginController::class . '::login');
        $event = $this->createMock(RequestEvent::class);
        $event->method('isMainRequest')->willReturn(true);
        $event->method('getRequest')->willReturn($request);

        // expect token validation not called
        $this->tokenManager->expects($this->never())->method('isTokenValid');

        // call tested middleware
        $this->middleware->onKernelRequest($event);
    }

    /**
     * Test middleware throws when token parameter is missing
     *
     * @return void
     */
    public function testHandlesMissingToken(): void
    {
        $request = Request::create('/secure', 'POST');
        $request->attributes->set('_route', 'secure_route');
        $request->attributes->set('_controller', self::class . '::testHandlesMissingToken');
        $event = $this->createMock(RequestEvent::class);
        $event->method('isMainRequest')->willReturn(true);
        $event->method('getRequest')->willReturn($request);

        // expect token validation not called
        $this->expectException(HttpException::class);
        $this->tokenManager->expects($this->never())->method('isTokenValid');

        // expect error manager to handle error
        $this->errorManager->expects($this->once())->method('handleError')->with('invalid csrf token', Response::HTTP_FORBIDDEN)
            ->willThrowException(new HttpException(Response::HTTP_FORBIDDEN));

        // call tested middleware
        $this->middleware->onKernelRequest($event);
    }

    /**
     * Test middleware throws when token validation fails
     *
     * @return void
     */
    public function testHandlesInvalidToken(): void
    {
        $request = Request::create('/secure', 'POST');
        $request->attributes->set('_route', 'secure_route');
        $request->attributes->set('_controller', self::class . '::testHandlesInvalidToken');
        $request->request->set('csrf_token', 'invalid');

        $event = $this->createMock(RequestEvent::class);
        $event->method('isMainRequest')->willReturn(true);
        $event->method('getRequest')->willReturn($request);

        // expect token validation to fail
        $this->expectException(HttpException::class);
        $this->tokenManager->expects($this->once())->method('isTokenValid')->with($this->callback(function (CsrfToken $token) {
            return $token->getId() === 'internal-csrf-token' && $token->getValue() === 'invalid';
        }))->willReturn(false);

        // expect error manager to handle error
        $this->errorManager->expects($this->once())->method('handleError')->with('invalid csrf token', Response::HTTP_FORBIDDEN)
            ->willThrowException(new HttpException(Response::HTTP_FORBIDDEN));

        // call tested middleware
        $this->middleware->onKernelRequest($event);
    }

    /**
     * Test middleware allows requests with valid tokens
     *
     * @return void
     */
    public function testAllowsValidToken(): void
    {
        $request = Request::create('/secure', 'POST');
        $request->attributes->set('_route', 'secure_route');
        $request->attributes->set('_controller', self::class . '::testAllowsValidToken');
        $request->request->set('csrf_token', 'valid-token');

        $event = $this->createMock(RequestEvent::class);
        $event->method('isMainRequest')->willReturn(true);
        $event->method('getRequest')->willReturn($request);

        // expect token validation to pass
        $this->tokenManager->expects($this->once())->method('isTokenValid')->with($this->callback(function (CsrfToken $token) {
            return $token->getId() === 'internal-csrf-token' && $token->getValue() === 'valid-token';
        }))->willReturn(true);

        // expect error manager not to handle error
        $this->errorManager->expects($this->never())->method('handleError');

        // call tested middleware
        $this->middleware->onKernelRequest($event);
    }
}
