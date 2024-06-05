<?php

namespace App\Tests\Middleware;

use App\Util\AppUtil;
use Psr\Log\LoggerInterface;
use App\Manager\ErrorManager;
use PHPUnit\Framework\TestCase;
use App\Middleware\MaintenanceMiddleware;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * Class MaintenanceMiddlewareTest
 *
 * Test the maintenance middleware
 *
 * @package App\Tests\Middleware
 */
class MaintenanceMiddlewareTest extends TestCase
{
    /**
     * Test if the maintenance mode is enabled
     *
     * @return void
     */
    public function testRequestWhenMaintenanceModeEnabled(): void
    {
        // create the app util mock
        $appUtilMock = $this->createMock(AppUtil::class);
        $appUtilMock->expects($this->once())
            ->method('isMaintenance')
            ->willReturn(true);

        // create the logger mock
        $loggerMock = $this->createMock(LoggerInterface::class);

        // create the error manager mock
        $errorManagerMock = $this->createMock(ErrorManager::class);

        // create an instance of the class under test
        $middleware = new MaintenanceMiddleware($appUtilMock, $loggerMock, $errorManagerMock);

        // create a RequestEvent with a dummy Request
        $event = $this->createMock(RequestEvent::class);

        // expect a response to be set
        $errorManagerMock->expects($this->once())
            ->method('getErrorView')
            ->with('maintenance')
            ->willReturn('Maintenance Mode Content');

        // expect the response to be set
        $event->expects($this->once())
            ->method('setResponse')
            ->with(new Response('Maintenance Mode Content', 503));

        // execute the middleware
        $middleware->onKernelRequest($event);
    }

    /**
     * Test if the maintenance mode is disabled
     *
     * @return void
     */
    public function testRequestWhenMaintenanceModeDisabled(): void
    {
        // create the app util mock
        $appUtilMock = $this->createMock(AppUtil::class);
        $appUtilMock->expects($this->once())
            ->method('isMaintenance')
            ->willReturn(false);

        // create the logger mock
        $loggerMock = $this->createMock(LoggerInterface::class);

        // create the error manager mock
        $errorManagerMock = $this->createMock(ErrorManager::class);

        // create an instance of the class under test
        $middleware = new MaintenanceMiddleware($appUtilMock, $loggerMock, $errorManagerMock);

        // create a RequestEvent with a dummy Request
        $event = $this->createMock(RequestEvent::class);

        // Expect no errors to be handled
        $errorManagerMock->expects($this->never())
            ->method('handleError');

        // expect no response to be set
        $event->expects($this->never())
            ->method('setResponse');

        // execute the middleware
        $middleware->onKernelRequest($event);
    }
}
