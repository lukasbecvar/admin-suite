<?php

namespace App\Tests\Manager;

use PHPUnit\Framework\TestCase;
use App\Manager\LogManager;
use App\Entity\Log;
use App\Manager\ErrorManager;
use App\Util\AppUtil;
use App\Util\VisitorInfoUtil;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;

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

    public function testLog(): void
    {
        $this->appUtilMock->method('isDatabaseLoggingEnabled')->willReturn(true);
        $this->appUtilMock->method('getLogLevel')->willReturn(3);
        $this->visitorInfoUtilMock->method('getUserAgent')->willReturn('Test User Agent');
        $this->visitorInfoUtilMock->method('getIP')->willReturn('127.0.0.1');

        $this->entityManagerMock->expects($this->once())->method('persist');
        $this->entityManagerMock->expects($this->once())->method('flush');

        $this->logManager->log('Test Name', 'Test Message');
    }
}
