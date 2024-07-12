<?php

namespace App\Tests\Middleware;

use App\Util\AppUtil;
use Psr\Log\LoggerInterface;
use App\Manager\ErrorManager;
use PHPUnit\Framework\TestCase;
use App\Middleware\SecurityCheckMiddleware;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * Class SecurityCheckMiddlewareTest
 *
 * Test the security check middleware
 *
 * @package App\Tests\Middleware
 */
class SecurityCheckMiddlewareTest extends TestCase
{
    /** @var AppUtil|MockObject */
    private AppUtil|MockObject $appUtilMock;

    /** @var LoggerInterface|MockObject */
    private LoggerInterface|MockObject $loggerMock;

    /** @var ErrorManager|MockObject */
    private ErrorManager|MockObject $errorManagerMock;

    /** @var SecurityCheckMiddleware */
    private SecurityCheckMiddleware $middleware;

    protected function setUp(): void
    {
        parent::setUp();

        // initialize mocks
        $this->appUtilMock = $this->createMock(AppUtil::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->errorManagerMock = $this->createMock(ErrorManager::class);

        // create an instance of the class under test
        $this->middleware = new SecurityCheckMiddleware(
            $this->appUtilMock,
            $this->loggerMock,
            $this->errorManagerMock
        );
    }

    /**
     * Test if ssl is enabled and ssl is not detected
     *
     * @return void
     */
    public function testRequestWhenSslEnabledAndSslNotDetected(): void
    {
        // configure mock expectations for this specific test
        $this->appUtilMock->expects($this->once())
            ->method('isSSLOnly')
            ->willReturn(true);
        $this->appUtilMock->expects($this->once())
            ->method('isSsl')
            ->willReturn(false);

        // create a RequestEvent with a dummy Request
        $event = $this->createMock(RequestEvent::class);

        // expect a response to be set
        $this->errorManagerMock->expects($this->once())
            ->method('getErrorView')
            ->with(426)
            ->willReturn('SSL Required Content');

        // expect the response to be set
        $event->expects($this->once())
            ->method('setResponse')
            ->with(new Response('SSL Required Content', Response::HTTP_UPGRADE_REQUIRED));

        // execute the middleware
        $this->middleware->onKernelRequest($event);
    }

    /**
     * Test if ssl is enabled and ssl is detected
     *
     * @return void
     */
    public function testRequestWhenSslEnabledAndSslDetected(): void
    {
        // configure mock expectations for this specific test
        $this->appUtilMock->expects($this->once())
            ->method('isSSLOnly')
            ->willReturn(true);
        $this->appUtilMock->expects($this->once())
            ->method('isSsl')
            ->willReturn(true);

        // create a RequestEvent with a dummy Request
        $event = $this->createMock(RequestEvent::class);

        // expect no errors to be handled
        $this->errorManagerMock->expects($this->never())
            ->method('handleError');

        // expect no response to be set
        $event->expects($this->never())
            ->method('setResponse');

        // execute the middleware
        $this->middleware->onKernelRequest($event);
    }

    /**
     * Test if the ssl is not enabled
     *
     * @return void
     */
    public function testRequestWhenSslNotEnabled(): void
    {
        // configure mock expectations for this specific test
        $this->appUtilMock->expects($this->once())
            ->method('isSSLOnly')
            ->willReturn(false);

        // create a RequestEvent with a dummy Request
        $event = $this->createMock(RequestEvent::class);

        // expect no errors to be handled
        $this->errorManagerMock->expects($this->never())
            ->method('handleError');

        // expect no response to be set
        $event->expects($this->never())
            ->method('setResponse');

        // execute the middleware
        $this->middleware->onKernelRequest($event);
    }
}
