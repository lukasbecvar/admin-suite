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
    /** @var AppUtil|MockObject */
    private AppUtil|MockObject $appUtilMock;

    /** @var LoggerInterface|MockObject */
    private LoggerInterface|MockObject $loggerMock;

    /** @var ErrorManager|MockObject */
    private ErrorManager|MockObject $errorManagerMock;

    /**
     * Sets up the mock objects before each test
     *
     * @return void
     */
    protected function setUp(): void
    {
        $this->appUtilMock = $this->createMock(AppUtil::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->errorManagerMock = $this->createMock(ErrorManager::class);
    }

    /**
     * Test if the maintenance mode is enabled
     *
     * @return void
     */
    public function testRequestWhenMaintenanceModeEnabled(): void
    {
        $this->appUtilMock->expects($this->once())
            ->method('isMaintenance')
            ->willReturn(true);

        $middleware = new MaintenanceMiddleware($this->appUtilMock, $this->loggerMock, $this->errorManagerMock);

        $event = $this->createMock(RequestEvent::class);

        $this->errorManagerMock->expects($this->once())
            ->method('getErrorView')
            ->with('maintenance')
            ->willReturn('Maintenance Mode Content');

        $event->expects($this->once())
            ->method('setResponse')
            ->with($this->callback(function ($response) {
                return $response instanceof Response && $response->getStatusCode() === 503 && $response->getContent() === 'Maintenance Mode Content';
            }));

        $middleware->onKernelRequest($event);
    }

    /**
     * Test if the maintenance mode is disabled
     *
     * @return void
     */
    public function testRequestWhenMaintenanceModeDisabled(): void
    {
        $this->appUtilMock->expects($this->once())
            ->method('isMaintenance')
            ->willReturn(false);

        $middleware = new MaintenanceMiddleware($this->appUtilMock, $this->loggerMock, $this->errorManagerMock);

        $event = $this->createMock(RequestEvent::class);

        $this->errorManagerMock->expects($this->never())
            ->method('handleError');

        $event->expects($this->never())
            ->method('setResponse');

        $middleware->onKernelRequest($event);
    }
}
