<?php

namespace App\Tests\Middleware;

use App\Util\AppUtil;
use App\Util\ServerUtil;
use Psr\Log\LoggerInterface;
use App\Manager\ErrorManager;
use PHPUnit\Framework\TestCase;
use App\Middleware\LinuxCheckMiddleware;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;

class LinuxCheckMiddlewareTest extends TestCase
{
    /** @var AppUtil|MockObject */
    private AppUtil|MockObject $appUtilMock;

    /** @var ServerUtil|MockObject */
    private ServerUtil|MockObject $serverUtilMock;

    /** @var LoggerInterface|MockObject */
    private LoggerInterface|MockObject $loggerMock;

    /** @var ErrorManager|MockObject */
    private ErrorManager|MockObject $errorManagerMock;

    protected function setUp(): void
    {
        $this->appUtilMock = $this->createMock(AppUtil::class);
        $this->serverUtilMock = $this->createMock(ServerUtil::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->errorManagerMock = $this->createMock(ErrorManager::class);
    }

    /**
     * Test if the linux system is detected
     *
     * @return void
     */
    public function testRequestLinuxSystem(): void
    {
        $this->serverUtilMock->expects($this->once())
            ->method('isSystemLinux')
            ->willReturn(true);

        $middleware = new LinuxCheckMiddleware(
            $this->appUtilMock,
            $this->serverUtilMock,
            $this->loggerMock,
            $this->errorManagerMock
        );

        $eventMock = $this->createMock(RequestEvent::class);
        $eventMock->expects($this->never())
            ->method('setResponse');

        $middleware->onKernelRequest($eventMock);
    }

    /**
     * Test if the linux system is not detected
     *
     * @return void
     */
    public function testRequestNonLinuxSystem(): void
    {
        $this->serverUtilMock->expects($this->once())
            ->method('isSystemLinux')
            ->willReturn(false);

        $this->appUtilMock->expects($this->once())
            ->method('isDevMode')
            ->willReturn(true);

        $this->errorManagerMock->expects($this->once())
            ->method('handleError')
            ->with(
                'This system is only for linux.',
                Response::HTTP_UPGRADE_REQUIRED
            );

        $this->errorManagerMock->expects($this->once())
            ->method('getErrorView')
            ->with(Response::HTTP_UPGRADE_REQUIRED)
            ->willReturn('<html><body><h1>Upgrade Required</h1></body></html>');

        $eventMock = $this->createMock(RequestEvent::class);
        $eventMock->expects($this->once())
            ->method('setResponse')
            ->with($this->callback(function (Response $response) {
                $this->assertEquals(Response::HTTP_UPGRADE_REQUIRED, $response->getStatusCode());
                $this->assertStringContainsString('Upgrade Required', (string) $response->getContent());
                return true;
            }));

        $middleware = new LinuxCheckMiddleware(
            $this->appUtilMock,
            $this->serverUtilMock,
            $this->loggerMock,
            $this->errorManagerMock
        );

        $middleware->onKernelRequest($eventMock);
    }
}
