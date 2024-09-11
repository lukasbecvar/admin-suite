<?php

namespace App\Tests\Middleware;

use App\Util\AppUtil;
use Psr\Log\LoggerInterface;
use App\Manager\ErrorManager;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use App\Middleware\DatabaseOnlineMiddleware;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;

/**
 * Class DatabaseOnlineMiddlewareTest
 *
 * Test the database online middleware
 *
 * @package App\Tests\Middleware
 */
class DatabaseOnlineMiddlewareTest extends TestCase
{
    private AppUtil & MockObject $appUtilMock;
    private Connection & MockObject $connectionMock;
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
        $this->connectionMock = $this->createMock(Connection::class);
        $this->loggerMock = $this->createMock(LoggerInterface::class);
        $this->errorManagerMock = $this->createMock(ErrorManager::class);
    }

    /**
     * Test if the database connection is successful
     *
     * @return void
     */
    public function testRequestWithSuccessfulDatabaseConnection(): void
    {
        // create the middleware instance
        $middleware = new DatabaseOnlineMiddleware(
            $this->appUtilMock,
            $this->connectionMock,
            $this->loggerMock,
            $this->errorManagerMock
        );

        // mock request event
        /** @var RequestEvent&MockObject $event */
        $event = $this->createMock(RequestEvent::class);

        // mock the error manager
        $this->errorManagerMock->expects($this->never())->method('handleError');

        // mock the response
        $event->expects($this->never())->method('setResponse');

        // execute the middleware
        $middleware->onKernelRequest($event);
    }

    /**
     * Test if the database offline error is handled
     *
     * @return void
     */
    public function testRequestWithFailedDatabaseConnection(): void
    {
        // create the middleware instance
        $middleware = new DatabaseOnlineMiddleware(
            $this->appUtilMock,
            $this->connectionMock,
            $this->loggerMock,
            $this->errorManagerMock
        );

        // mock request event
        /** @var RequestEvent&MockObject $event */
        $event = $this->createMock(RequestEvent::class);

        // mock the database connection
        $this->connectionMock->expects($this->once())
            ->method('executeQuery')->willThrowException(new \Exception('Database connection failed'));

        // mock the error manager
        $this->errorManagerMock->expects($this->once())
            ->method('getErrorView')->with(Response::HTTP_INTERNAL_SERVER_ERROR)->willReturn('Internal Server Error Content');

        // mock the response
        $event->expects($this->once())
            ->method('setResponse')->with($this->callback(function ($response) {
                return $response instanceof Response && $response->getStatusCode() === Response::HTTP_INTERNAL_SERVER_ERROR && $response->getContent() === 'Internal Server Error Content';
            }));

        // execute the middleware
        $middleware->onKernelRequest($event);
    }
}
