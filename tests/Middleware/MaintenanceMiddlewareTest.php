<?php

namespace App\Tests\Middleware;

use App\Util\AppUtil;
use Psr\Log\LoggerInterface;
use App\Manager\ErrorManager;
use PHPUnit\Framework\TestCase;
use App\Middleware\MaintenanceMiddleware;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * Class MaintenanceMiddlewareTest
 *
 * Test cases for maintenance middleware
 *
 * @package App\Tests\Middleware
 */
#[CoversClass(MaintenanceMiddleware::class)]
class MaintenanceMiddlewareTest extends TestCase
{
    private MaintenanceMiddleware $middleware;
    private AppUtil & MockObject $appUtilMock;
    private LoggerInterface & MockObject $loggerMock;
    private ErrorManager & MockObject $errorManagerMock;

    protected function setUp(): void
    {
        // mock dependencies
        $this->appUtilMock = $this->createMock(AppUtil::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->errorManagerMock = $this->createMock(ErrorManager::class);

        // create middleware instance
        $this->middleware = new MaintenanceMiddleware(
            $this->appUtilMock,
            $this->errorManagerMock,
            $this->loggerMock
        );
    }

    /**
     * Test request when maintenance is enabled
     *
     * @return void
     */
    public function testRequestWhenMaintenanceModeEnabled(): void
    {
        // mock request event
        /** @var RequestEvent & MockObject $event */
        $event = $this->createMock(RequestEvent::class);

        // simulate maintenance mode enabled
        $this->appUtilMock->expects($this->once())->method('isMaintenance')->willReturn(true);

        // expect get error view call
        $this->errorManagerMock->expects($this->once())->method('getErrorView')->with('maintenance')
            ->willReturn('Maintenance Mode Content');

        // expect middleware response
        $event->expects($this->once())
            ->method('setResponse')
            ->with($this->callback(function ($response) {
                return $response instanceof Response &&
                    $response->getStatusCode() === Response::HTTP_SERVICE_UNAVAILABLE &&
                    $response->getContent() === 'Maintenance Mode Content';
            }));

        // call tested middleware
        $this->middleware->onKernelRequest($event);
    }

    /**
     * Test request when maintenance is disabled
     *
     * @return void
     */
    public function testRequestWhenMaintenanceModeDisabled(): void
    {
        // mock request event
        /** @var RequestEvent & MockObject $event */
        $event = $this->createMock(RequestEvent::class);

        // simulate maintenance mode disabled
        $this->appUtilMock->expects($this->once())->method('isMaintenance')->willReturn(false);

        // expect handle error not called
        $this->errorManagerMock->expects($this->never())->method('handleError');

        // expect response not set
        $event->expects($this->never())->method('setResponse');

        // call tested middleware
        $this->middleware->onKernelRequest($event);
    }
}
