<?php

namespace App\Tests\Manager;

use Exception;
use App\Entity\Log;
use App\Util\AppUtil;
use App\Util\CookieUtil;
use App\Util\SessionUtil;
use App\Manager\LogManager;
use App\Util\FileSystemUtil;
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
 * Test cases for log manager
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
    private FileSystemUtil & MockObject $fileSystemUtilMock;
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
        $this->fileSystemUtilMock = $this->createMock(FileSystemUtil::class);
        $this->visitorInfoUtilMock = $this->createMock(VisitorInfoUtil::class);
        $this->entityManagerMock = $this->createMock(EntityManagerInterface::class);

        // create the log manager instance
        $this->logManager = new LogManager(
            $this->appUtilMock,
            $this->cookieUtilMock,
            $this->sessionUtilMock,
            $this->errorManagerMock,
            $this->repositoryMock,
            $this->fileSystemUtilMock,
            $this->visitorInfoUtilMock,
            $this->entityManagerMock
        );
    }

    /**
     * Test save log when level is too low
     *
     * @return void
     */
    public function testSaveLogWhenLevelIsTooLow(): void
    {
        // mock get log config
        $this->appUtilMock->method('isDatabaseLoggingEnabled')->willReturn(true);
        $this->appUtilMock->method('getEnvValue')->with('LOG_LEVEL')->willReturn((string) LogManager::LEVEL_WARNING);

        // expect persist and flush not to be called
        $this->entityManagerMock->expects($this->never())->method('persist');
        $this->entityManagerMock->expects($this->never())->method('flush');

        // call tested method
        $this->logManager->log('TestLog', 'This is a test log message.', LogManager::LEVEL_INFO);
    }

    /**
     * Test save log when messages is connection refused
     *
     * @return void
     */
    public function testSaveLogWhenMessagesIsConnectionRefused(): void
    {
        // expect persist and flush not to be called
        $this->entityManagerMock->expects($this->never())->method('persist');
        $this->entityManagerMock->expects($this->never())->method('flush');

        // call tested method
        $this->logManager->log('TestLog', 'Connection refused', LogManager::LEVEL_CRITICAL);
    }

    /**
     * Test save log when flush throws exception
     *
     * @return void
     */
    public function testSaveLogWhenFlushThrowsException(): void
    {
        // mock get log config
        $this->appUtilMock->method('isDatabaseLoggingEnabled')->willReturn(true);
        $this->appUtilMock->method('getEnvValue')->with('LOG_LEVEL')->willReturn('4');

        // mock get visitor info
        $this->visitorInfoUtilMock->method('getIP')->willReturn('127.0.0.1');
        $this->visitorInfoUtilMock->method('getUserAgent')->willReturn('UnitTestAgent');

        // mock user identifier getter
        $this->sessionUtilMock->method('getSessionValue')->with('user-identifier', 0)->willReturn(1);

        // expect process method call
        $this->entityManagerMock->expects($this->once())->method('persist');
        $this->entityManagerMock->method('flush')->will($this->throwException(
            new Exception('Database error')
        ));

        // expect error handler call
        $this->errorManagerMock->expects($this->once())->method('handleError')->with(
            'log-error: Database error',
            Response::HTTP_INTERNAL_SERVER_ERROR
        );

        // call tested method
        $this->logManager->log('TestLog', 'This is a test log message.', LogManager::LEVEL_INFO);
    }

    /**
     * Test save log with success result
     *
     * @return void
     */
    public function testSaveLogWithSuccessResult(): void
    {
        // mock get log config
        $this->appUtilMock->method('isDatabaseLoggingEnabled')->willReturn(true);
        $this->appUtilMock->method('getEnvValue')->with('LOG_LEVEL')->willReturn('4');

        // mock get visitor info
        $this->visitorInfoUtilMock->method('getIP')->willReturn('127.0.0.1');
        $this->visitorInfoUtilMock->method('getUserAgent')->willReturn('UnitTestAgent');

        // mock user identifier getter
        $this->sessionUtilMock->method('getSessionValue')->with('user-identifier', 0)->willReturn(1);

        // expect process method to be called
        $this->entityManagerMock->expects($this->once())->method('persist');
        $this->entityManagerMock->expects($this->once())->method('flush');

        // call tested method
        $this->logManager->log('TestLog', 'This is a test log message.', LogManager::LEVEL_INFO);
    }

    /**
     * Test enable anti-log feature
     *
     * @return void
     */
    public function testEnableAntiLogFeature(): void
    {
        // mock get token from config
        $this->appUtilMock->method('getEnvValue')->willReturn('test-token');

        // expect set cookie call
        $this->cookieUtilMock->expects($this->once())->method('set')->with(
            'anti-log',
            'test-token',
                 $this->greaterThan(time())
        );

        // call tested method
        $this->logManager->setAntiLog();
    }

    /**
     * Test disable anti-log feature
     *
     * @return void
     */
    public function testDisableAntiLogFeature(): void
    {
        // expect unset method to be called
        $this->cookieUtilMock->expects($this->once())->method('unset')->with('anti-log');

        // call tested method
        $this->logManager->unSetAntiLog();
    }

    /**
     * Test check if anti-log feature is enabled when cookie is set
     *
     * @return void
     */
    public function testCheckIfAntiLogFeatureIsEnabledWhenCookieIsSet(): void
    {
        // mock cookie util
        $this->cookieUtilMock->method('isCookieSet')->willReturn(true);
        $this->cookieUtilMock->method('get')->willReturn('test-token');

        // mock app util
        $this->appUtilMock->method('getEnvValue')->willReturn('test-token');

        // call tested method
        $result = $this->logManager->isAntiLogEnabled();

        // assert result
        $this->assertTrue($result);
    }

    /**
     * Test check if anti-log feature is enabled when cookie is not set
     *
     * @return void
     */
    public function testCheckIfAntiLogFeatureIsEnabledWhenCookieIsNotSet(): void
    {
        // mock cookie util
        $this->cookieUtilMock->method('isCookieSet')->willReturn(false);

        // call tested method
        $result = $this->logManager->isAntiLogEnabled();

        // assert result
        $this->assertFalse($result);
    }

    /**
     * Test check if anti-log feature is enabled when token is invalid
     *
     * @return void
     */
    public function testCheckIfAntiLogFeatureIsEnabledWhenTokenIsInvalid(): void
    {
        // mock cookie util
        $this->cookieUtilMock->method('isCookieSet')->willReturn(true);
        $this->cookieUtilMock->method('get')->willReturn('invalid-token');

        // mock app util
        $this->appUtilMock->method('getEnvValue')->willReturn('test-token');

        // call tested method
        $result = $this->logManager->isAntiLogEnabled();

        // assert result
        $this->assertFalse($result);
    }

    /**
     * Test get logs count
     *
     * @return void
     */
    public function testGetLogsCount(): void
    {
        // expect count method to be called
        $this->repositoryMock->expects($this->once())->method('count');

        // call tested method
        $result = $this->logManager->getLogsCountWhereStatus();

        // assert result
        $this->assertIsInt($result);
    }

    /**
     * Test get auth logs count
     *
     * @return void
     */
    public function testGetAuthLogsCount(): void
    {
        // expect count method to be called
        $this->repositoryMock->expects($this->once())->method('count')->with([
            'name' => 'authenticator',
            'status' => 'UNREADED'
        ]);

        // call tested method
        $result = $this->logManager->getAuthLogsCount();

        // assert result
        $this->assertIsInt($result);
    }

    /**
     * Test get logs
     *
     * @return void
     */
    public function testGetLogs(): void
    {
        // expect findBy method to be called
        $this->repositoryMock->expects($this->once())->method('findBy');

        // call tested method
        $result = $this->logManager->getLogsWhereStatus();

        // assert result
        $this->assertIsArray($result);
    }

    /**
     * Test get monitoring logs
     *
     * @return void
     */
    public function testGetMonitoringLogs(): void
    {
        // expect findBy method to be called
        $this->repositoryMock->expects($this->once())->method('findBy')->with([
            'name' => 'monitoring'
        ]);

        // call tested method
        $result = $this->logManager->getMonitoringLogs(10);

        // assert result
        $this->assertIsArray($result);
    }

    /**
     * Test set all logs to readed
     *
     * @return void
     */
    public function testSetAllLogsToReaded(): void
    {
        // expect findBy method to be called
        $this->repositoryMock->expects($this->once())->method('findBy')->with([
            'status' => 'UNREADED'
        ]);

        // expect flush to be called once
        $this->entityManagerMock->expects($this->once())->method('flush');

        // call tested method
        $this->logManager->setAllLogsToReaded();
    }

    /**
     * Test update log status by ID when an exception is thrown during flush
     *
     * @return void
     */
    public function testUpdateLogStatusByIdWhenAnExceptionIsThrownDuringFlush(): void
    {
        $logId = 1;
        $newStatus = 'READED';

        // mock Log entity
        $logMock = $this->createMock(Log::class);
        $logMock->expects($this->once())->method('setStatus')->with($newStatus);

        // expect find method to be called
        $this->repositoryMock->expects($this->once())->method('find')->with($logId)
            ->willReturn($logMock);

        // simulate an exception on flush
        $this->entityManagerMock->expects($this->once())->method('flush')->willThrowException(
            new Exception('Database error')
        );

        // expect error handler call
        $this->errorManagerMock->expects($this->once())->method('handleError')->with(
            'error to update log status: Database error',
            Response::HTTP_INTERNAL_SERVER_ERROR
        );

        // call tested method
        $this->logManager->updateLogStatusById($logId, $newStatus);
    }

    /**
     * Test update log status by ID when log is found and status is updated
     *
     * @return void
     */
    public function testUpdateLogStatusByIdWhenLogIsFoundAndStatusIsUpdated(): void
    {
        $logId = 1;
        $newStatus = 'READED';

        // mock Log entity
        $logMock = $this->createMock(Log::class);
        $logMock->expects($this->once())->method('setStatus')->with($newStatus);

        // expect find method to be called
        $this->repositoryMock->expects($this->once())->method('find')->with($logId)
            ->willReturn($logMock);

        // expect flush method to be called
        $this->entityManagerMock->expects($this->once())->method('flush');

        // call tested method
        $this->logManager->updateLogStatusById($logId, $newStatus);
    }

    /**
     * Test get system logs
     *
     * @return void
     */
    public function testGetSystemLogs(): void
    {
        // expect file system call
        $this->fileSystemUtilMock->expects($this->once())->method('getFilesList');

        // call tested method
        $result = $this->logManager->getSystemLogs();

        // assert result
        $this->assertIsArray($result);
    }

    /**
     * Test get system log content
     *
     * @return void
     */
    public function testGetSystemLogContent(): void
    {
        // mock get content
        $this->fileSystemUtilMock->method('getFileContent')->willReturn('file content');

        // call tested method
        $result = $this->logManager->getSystemLogContent('test.log');

        // assert result
        $this->assertEquals('file content', $result);
    }
}
