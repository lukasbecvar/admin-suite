<?php

namespace App\Tests\Middleware;

use App\Util\AppUtil;
use Psr\Log\LoggerInterface;
use App\Manager\ErrorManager;
use PHPUnit\Framework\TestCase;
use App\Middleware\MaintenanceMiddleware;
use PHPUnit\Framework\MockObject\MockObject;
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
    private MaintenanceMiddleware $middleware;
    private AppUtil & MockObject $appUtilMock;
    private LoggerInterface & MockObject $loggerMock;
    private ErrorManager & MockObject $errorManagerMock;

    /**
     * Sets up the mock objects before each test
     *
     * @return void
     */
    protected function setUp(): void
    {
        // mock dependencies
        $this->appUtilMock = $this->createMock(AppUtil::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->errorManagerMock = $this->createMock(ErrorManager::class);

        // create the middleware instance
        $this->middleware = new MaintenanceMiddleware(
            $this->appUtilMock,
            $this->errorManagerMock,
            $this->loggerMock
        );
    }

    /**
     * Test if the maintenance mode is enabled
     *
     * @return void
     */
    public function testRequestWhenMaintenanceModeEnabled(): void
    {
        // mock the app util
        $this->appUtilMock->expects($this->once())->method('isMaintenance')->willReturn(true);

        // mock request event
        /** @var RequestEvent & MockObject $event */
        $event = $this->createMock(RequestEvent::class);

        // mock the error manager
        $this->errorManagerMock->expects($this->once())
            ->method('getErrorView')
            ->with('maintenance')
            ->willReturn('Maintenance Mode Content');

        // mock the response
        $event->expects($this->once())
            ->method('setResponse')
            ->with($this->callback(function ($response) {
                return $response instanceof Response &&
                    $response->getStatusCode() === Response::HTTP_SERVICE_UNAVAILABLE &&
                    $response->getContent() === 'Maintenance Mode Content';
            }));

        // execute the middleware
        $this->middleware->onKernelRequest($event);
    }

    /**
     * Test if the maintenance mode is disabled
     *
     * @return void
     */
    public function testRequestWhenMaintenanceModeDisabled(): void
    {
        // mock the app util
        $this->appUtilMock->expects($this->once())->method('isMaintenance')->willReturn(false);

        // mock request event
        /** @var RequestEvent & MockObject $event */
        $event = $this->createMock(RequestEvent::class);

        // mock the error manager
        $this->errorManagerMock->expects($this->never())->method('handleError');

        // mock the response
        $event->expects($this->never())->method('setResponse');

        // execute the middleware
        $this->middleware->onKernelRequest($event);
    }
}
