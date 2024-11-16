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
 * Test the assets check middleware
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
        // create mock objects for dependencies
        $this->appUtilMock = $this->createMock(AppUtil::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);

        // create an instance of the class under test
        $this->middleware = new AssetsCheckMiddleware($this->appUtilMock, $this->loggerMock);
    }

    /**
     * Test if the assets do not exist and the middleware handles the error response
     *
     * @return void
     */
    public function testRequestWithErrorResponse(): void
    {
        /** @var RequestEvent&MockObject $eventMock */
        $eventMock = $this->createMock(RequestEvent::class);

        // set up expectations for the mock objects
        $this->appUtilMock->expects($this->once())
            ->method('isAssetsExist')->willReturn(false);

        // mock the logger error method
        $this->loggerMock->expects($this->once())
            ->method('error')->with('build resources not found');

        // mock the setResponse method
        $eventMock->expects($this->once())
            ->method('setResponse')->with($this->callback(function ($response) {
                return $response instanceof Response
                    && $response->getStatusCode() === Response::HTTP_INTERNAL_SERVER_ERROR
                    && $response->getContent() === 'Error: build resources not found, please contact service administrator & report this bug on email: ' . ($_ENV['ADMIN_CONTACT'] ?? 'unknown');
            }));

        // call middleware tested method
        $this->middleware->onKernelRequest($eventMock);
    }

    /**
     * Test handle request without error response
     *
     * @return void
     */
    public function testRequestWithoutErrorResponse(): void
    {
        /** @var RequestEvent&MockObject $eventMock */
        $eventMock = $this->createMock(RequestEvent::class);

        // set up expectations for the mock objects
        $this->appUtilMock->expects($this->once())
            ->method('isAssetsExist')->willReturn(true);

        // mock the logger error method
        $this->loggerMock->expects($this->never())
            ->method('error');

        // mock the setResponse method
        $eventMock->expects($this->never())
            ->method('setResponse');

        // call middleware tested method
        $this->middleware->onKernelRequest($eventMock);
    }
}
