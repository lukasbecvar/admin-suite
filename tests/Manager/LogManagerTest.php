<?php

namespace App\Tests\Manager;

use App\Util\AppUtil;
use App\Manager\LogManager;
use App\Util\VisitorInfoUtil;
use App\Manager\ErrorManager;
use PHPUnit\Framework\TestCase;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class LogManagerTest
 *
 * Test the log manager
 *
 * @package App\Tests\Manager
 */
class LogManagerTest extends TestCase
{
    /** @var AppUtil|MockObject */
    private AppUtil|MockObject $appUtilMock;

    /** @var ErrorManager|MockObject */
    private ErrorManager|MockObject $errorManagerMock;

    /** @var VisitorInfoUtil|MockObject */
    private VisitorInfoUtil|MockObject $visitorInfoUtilMock;

    /** @var EntityManagerInterface|MockObject */
    private EntityManagerInterface|MockObject $entityManagerMock;

    /** @var LogManager */
    private LogManager $logManager;

    protected function setUp(): void
    {
        $this->appUtilMock = $this->createMock(AppUtil::class);
        $this->errorManagerMock = $this->createMock(ErrorManager::class);
        $this->visitorInfoUtilMock = $this->createMock(VisitorInfoUtil::class);
        $this->entityManagerMock = $this->createMock(EntityManagerInterface::class);

        $this->logManager = new LogManager(
            $this->appUtilMock,
            $this->errorManagerMock,
            $this->visitorInfoUtilMock,
            $this->entityManagerMock
        );
    }

    /**
     * Test log
     *
     * @return void
     */
    public function testLog(): void
    {
        // mock the app util
        $this->appUtilMock->method('isDatabaseLoggingEnabled')->willReturn(true);
        $this->appUtilMock->method('getLogLevel')->willReturn(3);

        // mock the visitor info util
        $this->visitorInfoUtilMock->method('getUserAgent')->willReturn('Test User Agent');
        $this->visitorInfoUtilMock->method('getIP')->willReturn('127.0.0.1');

        // mock the entity manager
        $this->entityManagerMock->expects($this->once())->method('persist');
        $this->entityManagerMock->expects($this->once())->method('flush');

        // call the log method
        $this->logManager->log('test name', 'test message');
    }
}
