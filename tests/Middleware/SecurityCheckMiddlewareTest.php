<?php

namespace App\Tests\Middleware;

use App\Util\AppUtil;
use Psr\Log\LoggerInterface;
use App\Manager\ErrorManager;
use PHPUnit\Framework\TestCase;
use App\Middleware\SecurityCheckMiddleware;
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
    /**
     * Test if ssl is enabled and ssl is not detected
     *
     * @return void
     */
    public function testRequestWhenSslEnabledAndSslNotDetected(): void
    {
        // create the AppUtil mock
        $appUtilMock = $this->createMock(AppUtil::class);
        $appUtilMock->expects($this->once())
            ->method('isSSLOnly')
            ->willReturn(true);
        $appUtilMock->expects($this->once())
            ->method('isSsl')
            ->willReturn(false);

        // create the logger mock
        $loggerMock = $this->createMock(LoggerInterface::class);

        // create the error manager mock
        $errorManagerMock = $this->createMock(ErrorManager::class);

        // create an instance of the class under test
        $middleware = new SecurityCheckMiddleware($appUtilMock, $loggerMock, $errorManagerMock);

        // create a RequestEvent with a dummy Request
        $event = $this->createMock(RequestEvent::class);

        // expect a response to be set
        $errorManagerMock->expects($this->once())
            ->method('getErrorView')
            ->with(426)
            ->willReturn('SSL Required Content');

        // expect the response to be set
        $event->expects($this->once())
            ->method('setResponse')
            ->with(new Response('SSL Required Content', 426));

        // execute the middleware
        $middleware->onKernelRequest($event);
    }

    /**
     * Test if ssl is enabled and detected
     *
     * @return void
     */
    public function testRequestWhenSslEnabledAndSslDetected(): void
    {
        // create the AppUtil mock
        $appUtilMock = $this->createMock(AppUtil::class);
        $appUtilMock->expects($this->once())
            ->method('isSSLOnly')
            ->willReturn(true);
        $appUtilMock->expects($this->once())
            ->method('isSsl')
            ->willReturn(true);

        // create the logger mock
        $loggerMock = $this->createMock(LoggerInterface::class);

        // create the error manager mock
        $errorManagerMock = $this->createMock(ErrorManager::class);

        // create an instance of the class under test
        $middleware = new SecurityCheckMiddleware($appUtilMock, $loggerMock, $errorManagerMock);

        // create a RequestEvent with a dummy Request
        $event = $this->createMock(RequestEvent::class);

        // expect no errors to be handled
        $errorManagerMock
            ->expects($this->never())
            ->method('handleError');

        // expect no response to be set
        $event
            ->expects($this->never())
            ->method('setResponse');

        // execute the middleware
        $middleware->onKernelRequest($event);
    }

    /**
     * Test if the ssl is not enabled
     *
     * @return void
     */
    public function testRequestWhenSslNotEnabled(): void
    {
        // create the AppUtil mock
        $appUtilMock = $this->createMock(AppUtil::class);
        $appUtilMock
            ->expects($this->once())
            ->method('isSSLOnly')
            ->willReturn(false);

        // create the logger mock
        $loggerMock = $this->createMock(LoggerInterface::class);

        // create the error manager mock
        $errorManagerMock = $this->createMock(ErrorManager::class);

        // create an instance of the class under test
        $middleware = new SecurityCheckMiddleware($appUtilMock, $loggerMock, $errorManagerMock);

        // create a RequestEvent with a dummy Request
        $event = $this->createMock(RequestEvent::class);

        // expect no errors to be handled
        $errorManagerMock
            ->expects($this->never())
            ->method('handleError');

        // expect no response to be set
        $event
            ->expects($this->never())
            ->method('setResponse');

        // execute the middleware
        $middleware->onKernelRequest($event);
    }
}
