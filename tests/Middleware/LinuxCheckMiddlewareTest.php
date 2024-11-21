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

/**
 * Class LinuxCheckMiddlewareTest
 *
 * Test cases for linux check middleware
 *
 * @package App\Tests\Middleware
 */
class LinuxCheckMiddlewareTest extends TestCase
{
    private LinuxCheckMiddleware $middleware;
    private AppUtil & MockObject $appUtilMock;
    private ServerUtil & MockObject $serverUtilMock;
    private LoggerInterface & MockObject $loggerMock;
    private ErrorManager & MockObject $errorManagerMock;

    protected function setUp(): void
    {
        // mock dependencies
        $this->appUtilMock = $this->createMock(AppUtil::class);
        $this->serverUtilMock = $this->createMock(ServerUtil::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->errorManagerMock = $this->createMock(ErrorManager::class);

        // create the middleware instance
        $this->middleware = new LinuxCheckMiddleware(
            $this->appUtilMock,
            $this->serverUtilMock,
            $this->loggerMock,
            $this->errorManagerMock
        );
    }

    /**
     * Test if the linux system is detected
     *
     * @return void
     */
    public function testRequestLinuxSystem(): void
    {
        // mock the server util
        $this->serverUtilMock->expects($this->once())->method('isSystemLinux')->willReturn(true);

        // mock request event
        /** @var RequestEvent & MockObject $eventMock */
        $eventMock = $this->createMock(RequestEvent::class);
        $eventMock->expects($this->never())->method('setResponse');

        // execute the middleware
        $this->middleware->onKernelRequest($eventMock);
    }

    /**
     * Test if the linux system is not detected
     *
     * @return void
     */
    public function testRequestNonLinuxSystem(): void
    {
        // mock the server util
        $this->serverUtilMock->expects($this->once())->method('isSystemLinux')->willReturn(false);

        // mock the app util
        $this->appUtilMock->expects($this->once())->method('isDevMode')->willReturn(true);

        // mock the error manager
        $this->errorManagerMock->expects($this->once())->method('handleError')->with(
            'This system is only for linux.',
            Response::HTTP_NOT_IMPLEMENTED
        );

        // mock the error manager
        $this->errorManagerMock->expects($this->once())->method('getErrorView')->with(Response::HTTP_NOT_IMPLEMENTED)->willReturn(
            '<html><body><h1>Upgrade Required</h1></body></html>'
        );

        // mock request event
        /** @var RequestEvent & MockObject $eventMock */
        $eventMock = $this->createMock(RequestEvent::class);
        $eventMock->expects($this->once())->method('setResponse')->with($this->callback(function (Response $response) {
            $this->assertEquals(Response::HTTP_NOT_IMPLEMENTED, $response->getStatusCode());
            $this->assertStringContainsString('Upgrade Required', (string) $response->getContent());
            return true;
        }));

        // execute the middleware
        $this->middleware->onKernelRequest($eventMock);
    }
}
