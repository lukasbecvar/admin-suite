<?php

namespace App\Tests\Middleware;

use App\Util\AppUtil;
use Twig\Environment;
use App\Manager\BanManager;
use App\Manager\AuthManager;
use PHPUnit\Framework\TestCase;
use App\Middleware\BannedCheckMiddleware;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * Class BannedCheckMiddlewareTest
 *
 * Test the banned check middleware
 *
 * @package App\Tests\Middleware
 */
class BannedCheckMiddlewareTest extends TestCase
{
    /**
     * Test request user banned
     *
     * @return void
     */
    public function testRequestUserBanned(): void
    {
        // mock dependency
        $appUtil = $this->createMock(AppUtil::class);
        $twig = $this->createMock(Environment::class);
        $banManager = $this->createMock(BanManager::class);
        $authManager = $this->createMock(AuthManager::class);

        // mock methods
        $appUtil->method('getAdminContactEmail')->willReturn('admin@example.com');
        $authManager->method('isUserLogedin')->willReturn(true);
        $authManager->method('getLoggedUserId')->willReturn(1);
        $banManager->method('isUserBanned')->with(1)->willReturn(true);
        $banManager->method('getBanReason')->with(1)->willReturn('Violation of terms');

        // mock twig
        $twig->method('render')->with('error/error-banned.twig', [
            'reason' => 'Violation of terms',
            'admin_contact' => 'admin@example.com'
        ])->willReturn('Rendered Template');

        // create the middleware
        $middleware = new BannedCheckMiddleware($appUtil, $twig, $banManager, $authManager);

        // mock the request
        $request = new Request();
        $event = $this->createMock(RequestEvent::class);
        $event->method('getRequest')->willReturn($request);

        // expect a response to be set
        $event->expects($this->once())
            ->method('setResponse')
            ->with($this->callback(function ($response) {
                return $response instanceof Response && $response->getStatusCode() === 403 && $response->getContent() === 'Rendered Template';
            }));

        // call the middleware method
        $middleware->onKernelRequest($event);
    }

    /**
     * Test request user not banned
     *
     * @return void
     */
    public function testRequestUserNotBanned(): void
    {
        // mock dependency
        $appUtil = $this->createMock(AppUtil::class);
        $twig = $this->createMock(Environment::class);
        $banManager = $this->createMock(BanManager::class);
        $authManager = $this->createMock(AuthManager::class);

        // mock methods
        $authManager->method('isUserLogedin')->willReturn(true);
        $authManager->method('getLoggedUserId')->willReturn(1);
        $banManager->method('isUserBanned')->with(1)->willReturn(false);

        // create the middleware
        $middleware = new BannedCheckMiddleware($appUtil, $twig, $banManager, $authManager);

        // mock the request
        $request = new Request();
        $event = $this->createMock(RequestEvent::class);
        $event->method('getRequest')->willReturn($request);

        // expect no response to be set
        $event->expects($this->never())->method('setResponse');

        // call the middleware method
        $middleware->onKernelRequest($event);
    }
}
