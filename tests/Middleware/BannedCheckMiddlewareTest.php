<?php

namespace App\Tests\Middleware;

use App\Util\AppUtil;
use Twig\Environment;
use App\Manager\BanManager;
use App\Manager\LogManager;
use App\Manager\AuthManager;
use PHPUnit\Framework\TestCase;
use App\Middleware\BannedCheckMiddleware;
use PHPUnit\Framework\MockObject\MockObject;
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
    private AppUtil & MockObject $appUtil;
    private Environment & MockObject $twig;
    private LogManager & MockObject $logManager;
    private BanManager & MockObject $banManager;
    private AuthManager & MockObject $authManager;

    /**
     * Sets up the mock objects before each test
     *
     * @return void
     */
    protected function setUp(): void
    {
        // mock dependencies
        $this->appUtil = $this->createMock(AppUtil::class);
        $this->twig = $this->createMock(Environment::class);
        $this->logManager = $this->createMock(LogManager::class);
        $this->banManager = $this->createMock(BanManager::class);
        $this->authManager = $this->createMock(AuthManager::class);
    }

    /**
     * Test request user banned
     *
     * @return void
     */
    public function testRequestUserBanned(): void
    {
        // mock the app util
        $this->appUtil->method('getEnvValue')->willReturn('admin@example.com');

        // mock the auth manager
        $this->authManager->method('isUserLogedin')->willReturn(true);
        $this->authManager->method('getLoggedUserId')->willReturn(1);

        // mock the ban manager
        $this->banManager->method('isUserBanned')->with(1)->willReturn(true);
        $this->banManager->method('getBanReason')->with(1)->willReturn('Violation of terms');

        // mock the twig environment
        $this->twig->method('render')->with('error/error-banned.twig', [
            'reason' => 'Violation of terms',
            'admin_contact' => 'admin@example.com'
        ])->willReturn('Rendered Template');

        // create the middleware instance
        $middleware = new BannedCheckMiddleware(
            $this->appUtil,
            $this->twig,
            $this->logManager,
            $this->banManager,
            $this->authManager
        );

        // mock request event
        $request = new Request();
        /** @var RequestEvent&MockObject $event */
        $event = $this->createMock(RequestEvent::class);
        $event->method('getRequest')->willReturn($request);

        // mock the response
        $event->expects($this->once())
            ->method('setResponse')
            ->with($this->callback(function ($response) {
                return $response instanceof Response && $response->getStatusCode() === 403 && $response->getContent() === 'Rendered Template';
            }));

        // execute the middleware
        $middleware->onKernelRequest($event);
    }

    /**
     * Test request user not banned
     *
     * @return void
     */
    public function testRequestUserNotBanned(): void
    {
        // mock the auth manager
        $this->authManager->method('isUserLogedin')->willReturn(true);
        $this->authManager->method('getLoggedUserId')->willReturn(1);

        // mock the ban manager
        $this->banManager->method('isUserBanned')->with(1)->willReturn(false);

        // create the middleware instance
        $middleware = new BannedCheckMiddleware(
            $this->appUtil,
            $this->twig,
            $this->logManager,
            $this->banManager,
            $this->authManager
        );

        // mock request event
        $request = new Request();
        /** @var RequestEvent&MockObject $event */
        $event = $this->createMock(RequestEvent::class);
        $event->method('getRequest')->willReturn($request);

        // mock the response
        $event->expects($this->never())->method('setResponse');

        // execute the middleware
        $middleware->onKernelRequest($event);
    }
}
