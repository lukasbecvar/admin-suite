<?php

namespace App\Tests\Manager;

use App\Entity\Log;
use App\Util\AppUtil;
use App\Util\CookieUtil;
use App\Util\SessionUtil;
use App\Manager\LogManager;
use App\Util\VisitorInfoUtil;
use App\Manager\ErrorManager;
use PHPUnit\Framework\TestCase;
use App\Repository\LogRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class LogManagerTest
 *
 * Test the log manager
 *
 * @package App\Tests\Manager
 */
class LogManagerTest extends TestCase
{
    private LogManager $logManager;
    private AppUtil & MockObject $appUtilMock;
    private CookieUtil & MockObject $cookieUtilMock;
    private SessionUtil & MockObject $sessionUtilMock;
    private LogRepository & MockObject $repositoryMock;
    private ErrorManager & MockObject $errorManagerMock;
    private VisitorInfoUtil & MockObject $visitorInfoUtilMock;
    private EntityManagerInterface & MockObject $entityManagerMock;

    protected function setUp(): void
    {
        // mock dependencies
        $this->appUtilMock = $this->createMock(AppUtil::class);
        $this->cookieUtilMock = $this->createMock(CookieUtil::class);
        $this->sessionUtilMock = $this->createMock(SessionUtil::class);
        $this->repositoryMock = $this->createMock(LogRepository::class);
        $this->errorManagerMock = $this->createMock(ErrorManager::class);
        $this->visitorInfoUtilMock = $this->createMock(VisitorInfoUtil::class);
        $this->entityManagerMock = $this->createMock(EntityManagerInterface::class);

        // create the log manager instance
        $this->logManager = new LogManager(
            $this->appUtilMock,
            $this->cookieUtilMock,
            $this->sessionUtilMock,
            $this->errorManagerMock,
            $this->visitorInfoUtilMock,
            $this->entityManagerMock
        );
    }

    /**
     * Test success save log
     *
     * @return void
     */
    public function testLogSuccess(): void
    {
        // mock dependencies
        $this->appUtilMock->method('isDatabaseLoggingEnabled')->willReturn(true);
        $this->appUtilMock->method('getEnvValue')->with('LOG_LEVEL')->willReturn('4');
        $this->visitorInfoUtilMock->method('getIP')->willReturn('127.0.0.1');
        $this->visitorInfoUtilMock->method('getUserAgent')->willReturn('UnitTestAgent');
        $this->sessionUtilMock->method('getSessionValue')->with('user-identifier', 0)->willReturn(1);

        // expect process method to be called
        $this->entityManagerMock->expects($this->once())->method('persist');
        $this->entityManagerMock->expects($this->once())->method('flush');

        // testing method call
        $this->logManager->log('TestLog', 'This is a test log message.', LogManager::LEVEL_INFO);
    }

    /**
     * Test log connection refused
     *
     * @return void
     */
    public function testLogConnectionRefused(): void
    {
        // mock dependencies
        $this->entityManagerMock->expects($this->never())->method('persist');
        $this->entityManagerMock->expects($this->never())->method('flush');

        // testing method call
        $this->logManager->log('TestLog', 'Connection refused', LogManager::LEVEL_CRITICAL);
    }

    /**
     * Test log level too low
     *
     * @return void
     */
    public function testLogLevelTooLow(): void
    {
        // mock dependencies
        $this->appUtilMock->method('isDatabaseLoggingEnabled')->willReturn(true);
        $this->appUtilMock->method('getEnvValue')->with('LOG_LEVEL')->willReturn('2');

        // expect process method to be called
        $this->entityManagerMock->expects($this->never())->method('persist');
        $this->entityManagerMock->expects($this->never())->method('flush');

        // testing method call
        $this->logManager->log('TestLog', 'This is a test log message.', LogManager::LEVEL_INFO);
    }

    /**
     * Test log database error
     *
     * @return void
     */
    public function testLogDatabaseError(): void
    {
        // mock dependencies
        $this->appUtilMock->method('isDatabaseLoggingEnabled')->willReturn(true);
        $this->appUtilMock->method('getEnvValue')->with('LOG_LEVEL')->willReturn('4');
        $this->visitorInfoUtilMock->method('getIP')->willReturn('127.0.0.1');
        $this->visitorInfoUtilMock->method('getUserAgent')->willReturn('UnitTestAgent');
        $this->sessionUtilMock->method('getSessionValue')->with('user-identifier', 0)->willReturn(1);

        // expect process method to be called
        $this->entityManagerMock->expects($this->once())->method('persist');
        $this->entityManagerMock->method('flush')->will($this->throwException(
            new \Exception('Database error')
        ));

        // mock error manager
        $this->errorManagerMock->expects($this->once())->method('handleError')->with(
            $this->stringContains('log-error: Database error'),
            Response::HTTP_INTERNAL_SERVER_ERROR
        );

        // testing method call
        $this->logManager->log('TestLog', 'This is a test log message.', LogManager::LEVEL_INFO);
    }

    /**
     * Test set anti-log token
     *
     * @return void
     */
    public function testSetAntiLog(): void
    {
        $this->appUtilMock->method('getEnvValue')->willReturn('test-token');

        // expect set method to be called
        $this->cookieUtilMock->expects($this->once())->method('set')
            ->with('anti-log', 'test-token', $this->greaterThan(time()));

        // call the setAntiLog method
        $this->logManager->setAntiLog();
    }

    /**
     * Test unset anti-log token
     *
     * @return void
     */
    public function testUnSetAntiLog(): void
    {
        // expect unset method to be called
        $this->cookieUtilMock->expects($this->once())->method('unset')->with('anti-log');

        // call the unSetAntiLog method
        $this->logManager->unSetAntiLog();
    }

    /**
     * Test is anti-log enabled
     *
     * @return void
     */
    public function testIsAntiLogEnabled(): void
    {
        // mock cookie util
        $this->cookieUtilMock->method('isCookieSet')->willReturn(true);
        $this->cookieUtilMock->method('get')->willReturn('test-token');

        // mock app util
        $this->appUtilMock->method('getEnvValue')->willReturn('test-token');

        // call the isAntiLogEnabled method and assert true
        $this->assertTrue($this->logManager->isAntiLogEnabled());
    }

    /**
     * Test is anti-log enabled when cookie is not set
     *
     * @return void
     */
    public function testIsAntiLogEnabledWhenCookieNotSet(): void
    {
        // mock cookie util
        $this->cookieUtilMock->method('isCookieSet')->willReturn(false);

        // call the isAntiLogEnabled method and assert false
        $this->assertFalse($this->logManager->isAntiLogEnabled());
    }

    /**
     * Test isAntiLogEnabled when token is invalid
     *
     * @return void
     */
    public function testIsAntiLogEnabledWhenTokenInvalid(): void
    {
        // mock cookie util
        $this->cookieUtilMock->method('isCookieSet')->willReturn(true);
        $this->cookieUtilMock->method('get')->willReturn('invalid-token');

        // mock app util
        $this->appUtilMock->method('getEnvValue')->willReturn('test-token');

        // call the isAntiLogEnabled method and assert false
        $this->assertFalse($this->logManager->isAntiLogEnabled());
    }

    /**
     * Test get logs count
     *
     * @return void
     */
    public function testGetLogsCount(): void
    {
        $count = $this->logManager->getLogsCountWhereStatus();

        $this->assertIsInt($count);
    }

    /**
     * Test get auth logs count
     *
     * @return void
     */
    public function testGetAuthLogsCount(): void
    {
        $count = $this->logManager->getAuthLogsCount();

        $this->assertIsInt($count);
    }

    /**
     * Test get logs
     *
     * @return void
     */
    public function testGetLogs(): void
    {
        $log1 = new Log();

        // mock repository to return logs with 'UNREADED' status
        $this->repositoryMock->method('findBy')->with(['status' => 'unread'])->willReturn([$log1]);

        // call get logs method
        $logs = $this->logManager->getLogsWhereStatus();

        // assert method response
        $this->assertIsArray($logs);
    }

    /**
     * Test set all logs to readed
     *
     * @return void
     */
    public function testSetAllLogsToReaded(): void
    {
        // mock repository to return logs with 'UNREADED' status
        $mockLog1 = new Log();
        $mockLog1->setStatus('UNREADED');

        $mockLog2 = new Log();
        $mockLog2->setStatus('UNREADED');

        $logs = [$mockLog1, $mockLog2];

        // mock entity manager
        $this->entityManagerMock->expects($this->once())
            ->method('getRepository')
            ->willReturn($this->repositoryMock);

        // mock repository
        $this->repositoryMock->expects($this->once())->method('findBy')
            ->with(['status' => 'UNREADED'])->willReturn($logs);

        // expect flush to be called once
        $this->entityManagerMock->expects($this->once())->method('flush');

        // call the method under test
        $this->logManager->setAllLogsToReaded();
    }

    /**
     * Test updateLogStatusById when log is found and status is updated
     *
     * @return void
     */
    public function testUpdateLogStatusByIdSuccess(): void
    {
        $logId = 1;
        $newStatus = 'READED';

        // mock log entity
        $logMock = $this->createMock(Log::class);
        $logMock->expects($this->once())->method('setStatus')->with($newStatus);

        // mock repository
        $repositoryMock = $this->createMock(LogRepository::class);
        $repositoryMock->expects($this->once())->method('find')->with($logId)->willReturn($logMock);

        // mock entity manager
        $this->entityManagerMock->expects($this->once())
            ->method('getRepository')->with(Log::class)->willReturn($repositoryMock);
        $this->entityManagerMock->expects($this->once())->method('flush');

        // call method
        $this->logManager->updateLogStatusById($logId, $newStatus);
    }

    /**
     * Test updateLogStatusById when an exception is thrown during flush
     *
     * @return void
     */
    public function testUpdateLogStatusByIdFlushException(): void
    {
        $logId = 1;
        $newStatus = 'READED';

        // mock log entity
        $logMock = $this->createMock(Log::class);
        $logMock->expects($this->once())->method('setStatus')->with($newStatus);

        // mock repository
        $repositoryMock = $this->createMock(LogRepository::class);
        $repositoryMock->expects($this->once())->method('find')->with($logId)->willReturn($logMock);

        // mock entity manager
        $this->entityManagerMock->expects($this->once())
        ->method('getRepository')->with(Log::class)->willReturn($repositoryMock);
        $this->entityManagerMock->expects($this->once())
            ->method('flush')->willThrowException(new \Exception('Test Exception'));

        // mock error manager
        $this->errorManagerMock->expects($this->once())->method('handleError')
            ->with(
                'error to update log status: Test Exception',
                Response::HTTP_INTERNAL_SERVER_ERROR
            );

        // call method
        $this->logManager->updateLogStatusById($logId, $newStatus);
    }
}
