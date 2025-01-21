<?php

namespace App\Tests\Middleware;

use App\Util\AppUtil;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;
use App\Middleware\AssetsCheckMiddleware;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * Class AssetsCheckMiddlewareTest
 *
 * Test cases for assets check middleware
 *
 * @package App\Tests\Middleware
 */
class AssetsCheckMiddlewareTest extends TestCase
{
    private AssetsCheckMiddleware $middleware;
    private AppUtil & MockObject $appUtilMock;
    private LoggerInterface & MockObject $loggerMock;

    protected function setUp(): void
    {
        // mock dependencies
        $this->appUtilMock = $this->createMock(AppUtil::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);

        // create middleware instance
        $this->middleware = new AssetsCheckMiddleware($this->appUtilMock, $this->loggerMock);
    }

    /**
     * Test handle request when assets not exist
     *
     * @return void
     */
    public function testRequestWhenAssetsNotExist(): void
    {
        /** @var RequestEvent&MockObject $eventMock */
        $eventMock = $this->createMock(RequestEvent::class);

        // simulate assets not exist
        $this->appUtilMock->expects($this->once())->method('isAssetsExist')->willReturn(false);

        // expect call logger
        $this->loggerMock->expects($this->once())->method('error')->with('build resources not found');

        // expect middleware response
        $eventMock->expects($this->once())
            ->method('setResponse')->with($this->callback(function ($response) {
                return $response instanceof Response
                    && $response->getStatusCode() === Response::HTTP_INTERNAL_SERVER_ERROR
                    && $response->getContent() === 'Error: build resources not found, please contact service administrator & report this bug on email: ' . ($_ENV['ADMIN_CONTACT'] ?? 'unknown');
            }));

        // call tested middleware
        $this->middleware->onKernelRequest($eventMock);
    }

    /**
     * Test handle request when assets exist
     *
     * @return void
     */
    public function testRequestWhenAssetsExist(): void
    {
        /** @var RequestEvent&MockObject $eventMock */
        $eventMock = $this->createMock(RequestEvent::class);

        // simulate assets exist
        $this->appUtilMock->expects($this->once())->method('isAssetsExist')->willReturn(true);

        // expect logger not called
        $this->loggerMock->expects($this->never())->method('error');

        // expect middleware response not called
        $eventMock->expects($this->never())->method('setResponse');

        // call tested middleware
        $this->middleware->onKernelRequest($eventMock);
    }
}
