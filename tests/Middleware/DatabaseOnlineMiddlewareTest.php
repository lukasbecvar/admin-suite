<?php

namespace App\Tests\Middleware;

use App\Util\AppUtil;
use Psr\Log\LoggerInterface;
use Doctrine\DBAL\Connection;
use App\Manager\ErrorManager;
use PHPUnit\Framework\TestCase;
use App\Middleware\DatabaseOnlineMiddleware;
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
    /**
     * Test if the database connection is successful
     *
     * @return void
     */
    public function testRequestWithSuccessfulDatabaseConnection(): void
    {
        // create mock objects for dependencies
        $appUtilMock = $this->createMock(AppUtil::class);
        $connectionMock = $this->createMock(Connection::class);
        $loggerMock = $this->createMock(LoggerInterface::class);
        $errorManagerMock = $this->createMock(ErrorManager::class);

        // create an instance of the class under test
        $middleware = new DatabaseOnlineMiddleware(
            $appUtilMock,
            $connectionMock,
            $loggerMock,
            $errorManagerMock
        );

        // create a RequestEvent with a dummy Request
        $event = $this->createMock(RequestEvent::class);

        // expect no errors to be handled
        $errorManagerMock->expects($this->never())
            ->method('handleError');

        // expect no response to be set
        $event->expects($this->never())
            ->method('setResponse');

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
        // create mock objects for dependencies
        $appUtilMock = $this->createMock(AppUtil::class);
        $connectionMock = $this->createMock(Connection::class);
        $loggerMock = $this->createMock(LoggerInterface::class);
        $errorManagerMock = $this->createMock(ErrorManager::class);

        // create an instance of the class under test
        $middleware = new DatabaseOnlineMiddleware(
            $appUtilMock,
            $connectionMock,
            $loggerMock,
            $errorManagerMock
        );

        // create a RequestEvent with a dummy Request
        $event = $this->createMock(RequestEvent::class);

        // zhrow an exception to simulate a failed database connection
        $connectionMock->expects($this->once())
            ->method('executeQuery')
            ->willThrowException(new \Exception('Database connection failed'));

        // expect a response to be set
        $errorManagerMock->expects($this->once())
            ->method('getErrorView')
            ->with(Response::HTTP_INTERNAL_SERVER_ERROR)
            ->willReturn('Internal Server Error Content');

        // expect the error to be handled
        $event->expects($this->once())
            ->method('setResponse')
            ->with(new Response('Internal Server Error Content', Response::HTTP_INTERNAL_SERVER_ERROR));

        // execute the middleware
        $middleware->onKernelRequest($event);
    }
}
