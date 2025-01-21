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
 * Test cases for banned check middleware
 *
 * @package App\Tests\Middleware
 */
class BannedCheckMiddlewareTest extends TestCase
{
    private AppUtil & MockObject $appUtil;
    private Environment & MockObject $twig;
    private BannedCheckMiddleware $middleware;
    private LogManager & MockObject $logManager;
    private BanManager & MockObject $banManager;
    private AuthManager & MockObject $authManager;

    protected function setUp(): void
    {
        // mock dependencies
        $this->appUtil = $this->createMock(AppUtil::class);
        $this->twig = $this->createMock(Environment::class);
        $this->logManager = $this->createMock(LogManager::class);
        $this->banManager = $this->createMock(BanManager::class);
        $this->authManager = $this->createMock(AuthManager::class);

        // create middleware instance
        $this->middleware = new BannedCheckMiddleware(
            $this->appUtil,
            $this->twig,
            $this->logManager,
            $this->banManager,
            $this->authManager
        );
    }

    /**
     * Test request when user is banned
     *
     * @return void
     */
    public function testRequestUserBanned(): void
    {
        // mock env config
        $this->appUtil->method('getEnvValue')->willReturn('admin@example.com');

        // simulate user logged in
        $this->authManager->method('isUserLogedin')->willReturn(true);
        $this->authManager->method('getLoggedUserId')->willReturn(1);

        // simulate user is banned
        $this->banManager->method('isUserBanned')->with(1)->willReturn(true);
        $this->banManager->method('getBanReason')->with(1)->willReturn('Violation of terms');

        // expect call twig render
        $this->twig->expects($this->once())->method('render')->with('error/error-banned.twig', [
            'reason' => 'Violation of terms',
            'admin_contact' => 'admin@example.com'
        ])->willReturn('Rendered Template');

        // mock request event
        $request = new Request();
        /** @var RequestEvent&MockObject $event */
        $event = $this->createMock(RequestEvent::class);
        $event->method('getRequest')->willReturn($request);

        // expect middleware response
        $event->expects($this->once())->method('setResponse')->with($this->callback(function ($response) {
            return $response instanceof Response &&
                $response->getStatusCode() === Response::HTTP_FORBIDDEN &&
                $response->getContent() === 'Rendered Template';
        }));

        // call tested middleware
        $this->middleware->onKernelRequest($event);
    }

    /**
     * Test request when user is not banned
     *
     * @return void
     */
    public function testRequestWhenUserIsNotBanned(): void
    {
        // simulate user logged in
        $this->authManager->method('isUserLogedin')->willReturn(true);
        $this->authManager->method('getLoggedUserId')->willReturn(1);

        // simulate user is not banned
        $this->banManager->method('isUserBanned')->with(1)->willReturn(false);

        // mock request event
        $request = new Request();
        /** @var RequestEvent&MockObject $event */
        $event = $this->createMock(RequestEvent::class);
        $event->method('getRequest')->willReturn($request);

        // expect response not set
        $event->expects($this->never())->method('setResponse');

        // call tested middleware
        $this->middleware->onKernelRequest($event);
    }
}
