<?php

namespace App\Tests\Middleware;

use App\Util\AppUtil;
use Psr\Log\LoggerInterface;
use PHPUnit\Framework\TestCase;
use App\Middleware\AssetsCheckMiddleware;
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
    /**
     * Test if the middleware is an instance of AssetsCheckMiddleware
     *
     * @return void
     */
    public function testRequestWithErrorResponse(): void
    {
        // create mock objects for dependencies
        $appUtilMock = $this->createMock(AppUtil::class);
        $eventMock = $this->createMock(RequestEvent::class);
        $loggerMock = $this->createMock(LoggerInterface::class);

        // set up expectations for the mock objects
        $appUtilMock->expects($this->once())
            ->method('isAssetsExist')
            ->willReturn(false);

        // mock the logger error method
        $loggerMock->expects($this->once())
            ->method('error')
            ->with('build resources not found');

        // mock the setResponse method
        $eventMock->expects($this->once())
            ->method('setResponse')
            ->with($this->callback(function ($response) {
                return $response instanceof Response
                    && $response->getStatusCode() === Response::HTTP_INTERNAL_SERVER_ERROR
                    && $response->getContent() === 'Error: build resources not found, please contact service administrator & report this bug on email: ' . $_ENV['ADMIN_CONTACT'];
            }));

        // create an instance of the class under test
        $middleware = new AssetsCheckMiddleware($appUtilMock, $loggerMock);

        // call the method under test
        $middleware->onKernelRequest($eventMock);
    }
}
