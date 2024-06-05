<?php

namespace App\Tests\Manager;

use App\Entity\Log;
use App\Util\AppUtil;
use App\Manager\LogManager;
use App\Manager\ErrorManager;
use PHPUnit\Framework\TestCase;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Class LogManagerTest
 *
 * Test the log manager
 *
 * @package App\Tests\Manager
 */
class LogManagerTest extends TestCase
{
    /**
     * Test if the log can be saved
     *
     * @return void
     */
    public function testLogDatabaseLoggingEnabled(): void
    {
        // create the app util mock
        $appUtilMock = $this->createMock(AppUtil::class);
        $appUtilMock->expects($this->once())
            ->method('isDatabaseLoggingEnabled')
            ->willReturn(true);
        $appUtilMock->expects($this->once())
            ->method('getLogLevel')
            ->willReturn(3);

        // create the entity manager mock
        $entityManagerMock = $this->createMock(EntityManagerInterface::class);
        $entityManagerMock->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(Log::class));
        $entityManagerMock->expects($this->once())
            ->method('flush');

        // create the error manager mock
        $errorManagerMock = $this->createMock(ErrorManager::class);
        $errorManagerMock->expects($this->never())
            ->method('handleError');

        // create the log manager
        $logManager = new LogManager($appUtilMock, $errorManagerMock, $entityManagerMock);

        // call log
        $logManager->log('TestLogName', 'Test log message');
    }

    /**
     * Test if the log can be saved
     *
     * @return void
     */
    public function testLogDatabaseLoggingDisabled(): void
    {
        // create the app util mock
        $appUtilMock = $this->createMock(AppUtil::class);
        $appUtilMock->expects($this->once())
            ->method('isDatabaseLoggingEnabled')
            ->willReturn(false);

        // create the entity manager mock
        $entityManagerMock = $this->createMock(EntityManagerInterface::class);
        $entityManagerMock->expects($this->never())
            ->method('persist');
        $entityManagerMock->expects($this->never())
            ->method('flush');

        // create the error manager mock
        $errorManagerMock = $this->createMock(ErrorManager::class);
        $errorManagerMock->expects($this->never())
            ->method('handleError');

        // create the log manager
        $logManager = new LogManager($appUtilMock, $errorManagerMock, $entityManagerMock);

        // call log
        $logManager->log('TestLogName', 'Test log message');
    }

    /**
     * Test if the log can be saved
     *
     * @return void
     */
    public function testLogBelowLogLevel(): void
    {
        // create the app util mock
        $appUtilMock = $this->createMock(AppUtil::class);
        $appUtilMock->expects($this->once())
            ->method('isDatabaseLoggingEnabled')
            ->willReturn(true);
        $appUtilMock->expects($this->once())
            ->method('getLogLevel')
            ->willReturn(2); // Set log level to 2

        // create the entity manager mock
        $entityManagerMock = $this->createMock(EntityManagerInterface::class);
        $entityManagerMock->expects($this->never())
            ->method('persist');
        $entityManagerMock->expects($this->never())
            ->method('flush');

        // create the error manager mock
        $errorManagerMock = $this->createMock(ErrorManager::class);
        $errorManagerMock->expects($this->never())
            ->method('handleError');

        // create the log manager
        $logManager = new LogManager($appUtilMock, $errorManagerMock, $entityManagerMock);

        // call log
        $logManager->log('TestLogName', 'Test log message', 3);
    }

    /**
     * Test if the log can be saved
     *
     * @return void
     */
    public function testLogException(): void
    {
        // create the app util mock
        $appUtilMock = $this->createMock(AppUtil::class);
        $appUtilMock->expects($this->once())
            ->method('isDatabaseLoggingEnabled')
            ->willReturn(true);
        $appUtilMock->expects($this->once())
            ->method('getLogLevel')
            ->willReturn(3);

        // create the entity manager mock
        $entityManagerMock = $this->createMock(EntityManagerInterface::class);
        $entityManagerMock->expects($this->once())
            ->method('persist')
            ->willThrowException(new \Exception('Database error'));
        $entityManagerMock->expects($this->never())
            ->method('flush');

        // create the error manager mock
        $errorManagerMock = $this->createMock(ErrorManager::class);
        $errorManagerMock->expects($this->once())
            ->method('handleError')
            ->with('log-error: Database error', 500);

        // create the log manager
        $logManager = new LogManager($appUtilMock, $errorManagerMock, $entityManagerMock);

        // call log
        $logManager->log('TestLogName', 'Test log message');
    }
}
