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
    /** @var AppUtil|MockObject */
    private AppUtil|MockObject $appUtilMock;

    /** @var Connection|MockObject */
    private Connection|MockObject $connectionMock;

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
        $middleware = new DatabaseOnlineMiddleware(
            $this->appUtilMock,
            $this->connectionMock,
            $this->loggerMock,
            $this->errorManagerMock
        );

        $event = $this->createMock(RequestEvent::class);

        $this->errorManagerMock->expects($this->never())
            ->method('handleError');

        $event->expects($this->never())
            ->method('setResponse');

        $middleware->onKernelRequest($event);
    }

    /**
     * Test if the database offline error is handled
     *
     * @return void
     */
    public function testRequestWithFailedDatabaseConnection(): void
    {
        $middleware = new DatabaseOnlineMiddleware(
            $this->appUtilMock,
            $this->connectionMock,
            $this->loggerMock,
            $this->errorManagerMock
        );

        $event = $this->createMock(RequestEvent::class);

        $this->connectionMock->expects($this->once())
            ->method('executeQuery')
            ->willThrowException(new \Exception('Database connection failed'));

        $this->errorManagerMock->expects($this->once())
            ->method('getErrorView')
            ->with(Response::HTTP_INTERNAL_SERVER_ERROR)
            ->willReturn('Internal Server Error Content');

        $event->expects($this->once())
            ->method('setResponse')
            ->with($this->callback(function ($response) {
                return $response instanceof Response && $response->getStatusCode() === Response::HTTP_INTERNAL_SERVER_ERROR && $response->getContent() === 'Internal Server Error Content';
            }));

        $middleware->onKernelRequest($event);
    }
}
