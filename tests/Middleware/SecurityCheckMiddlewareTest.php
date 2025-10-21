<?php

namespace App\Tests\Middleware;

use App\Util\AppUtil;
use App\Manager\ErrorManager;
use PHPUnit\Framework\TestCase;
use App\Middleware\SecurityCheckMiddleware;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * Class SecurityCheckMiddlewareTest
 *
 * Test cases for security check middleware
 *
 * @package App\Tests\Middleware
 */
#[CoversClass(SecurityCheckMiddleware::class)]
class SecurityCheckMiddlewareTest extends TestCase
{
    private AppUtil & MockObject $appUtilMock;
    private SecurityCheckMiddleware $middleware;
    private ErrorManager & MockObject $errorManagerMock;

    protected function setUp(): void
    {
        // mock dependencies
        $this->appUtilMock = $this->createMock(AppUtil::class);
        $this->errorManagerMock = $this->createMock(ErrorManager::class);

        // create middleware instance
        $this->middleware = new SecurityCheckMiddleware(
            $this->appUtilMock,
            $this->errorManagerMock
        );
    }

    /**
     * Test request when ssl is enabled and ssl is not detected
     *
     * @return void
     */
    public function testRequestWhenSslEnabledAndSslNotDetected(): void
    {
        /** @var RequestEvent & MockObject $event */
        $event = $this->createMock(RequestEvent::class);

        // simulate ssl only
        $this->appUtilMock->expects($this->once())->method('isSSLOnly')->willReturn(true);

        // simulate ssl not detected
        $this->appUtilMock->expects($this->once())->method('isSsl')->willReturn(false);

        // expect get error view to be called
        $this->errorManagerMock->expects($this->once())
            ->method('getErrorView')->with(Response::HTTP_UPGRADE_REQUIRED)->willReturn('SSL Required Content');

        // expect middleware response
        $event->expects($this->once())->method('setResponse')
            ->with(new Response('SSL Required Content', Response::HTTP_UPGRADE_REQUIRED));

        // call tested middleware
        $this->middleware->onKernelRequest($event);
    }

    /**
     * Test request when ssl is enabled and ssl is detected
     *
     * @return void
     */
    public function testRequestWhenSslEnabledAndSslDetected(): void
    {
        /** @var RequestEvent & MockObject $event */
        $event = $this->createMock(RequestEvent::class);

        // simulate ssl only enabled
        $this->appUtilMock->expects($this->once())->method('isSSLOnly')->willReturn(true);

        // simulate ssl detected
        $this->appUtilMock->expects($this->once())->method('isSsl')->willReturn(true);

        // expect error handling to be called
        $this->errorManagerMock->expects($this->never())->method('handleError');

        // expect response not set
        $event->expects($this->never())->method('setResponse');

        // call tested middleware
        $this->middleware->onKernelRequest($event);
    }

    /**
     * Test request when ssl only is not enabled
     *
     * @return void
     */
    public function testRequestWhenSslOnlyNotEnabled(): void
    {
        /** @var RequestEvent & MockObject $event */
        $event = $this->createMock(RequestEvent::class);

        // simulate ssl only not enabled
        $this->appUtilMock->expects($this->once())->method('isSSLOnly')->willReturn(false);

        // expect handle error not called
        $this->errorManagerMock->expects($this->never())->method('handleError');

        // expect response not set
        $event->expects($this->never())->method('setResponse');

        // call tested middleware
        $this->middleware->onKernelRequest($event);
    }
}
