<?php

namespace App\Tests\Manager;

use App\Util\AppUtil;
use App\Util\ServerUtil;
use App\Manager\LogManager;
use App\Manager\EmailManager;
use App\Manager\ErrorManager;
use PHPUnit\Framework\TestCase;
use App\Manager\ServiceManager;
use App\Entity\MonitoringStatus;
use App\Manager\MonitoringManager;
use App\Manager\NotificationsManager;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use App\Repository\MonitoringStatusRepository;
use App\Util\CacheUtil;

/**
 * Class MonitoringManagerTest
 *
 * Test for monitoring manager class
 *
 * @package App\Tests\Manager
 */
class MonitoringManagerTest extends TestCase
{
    private AppUtil & MockObject $appUtilMock;
    private LogManager & MockObject $logManager;
    private MonitoringManager $monitoringManager;
    private CacheUtil & MockObject $cacheUtilMock;
    private ServerUtil & MockObject $serverUtilMock;
    private EmailManager & MockObject $emailManagerMock;
    private ErrorManager & MockObject $errorManagerMock;
    private ServiceManager & MockObject $serviceManagerMock;
    private EntityManagerInterface & MockObject $entityManagerMock;
    private MonitoringStatusRepository & MockObject $repositoryMock;
    private NotificationsManager & MockObject $notificationsManagerMock;

    protected function setUp(): void
    {
        // mock dependencies
        $this->appUtilMock = $this->createMock(AppUtil::class);
        $this->logManager = $this->createMock(LogManager::class);
        $this->cacheUtilMock = $this->createMock(CacheUtil::class);
        $this->serverUtilMock = $this->createMock(ServerUtil::class);
        $this->emailManagerMock = $this->createMock(EmailManager::class);
        $this->errorManagerMock = $this->createMock(ErrorManager::class);
        $this->serviceManagerMock = $this->createMock(ServiceManager::class);
        $this->entityManagerMock = $this->createMock(EntityManagerInterface::class);
        $this->repositoryMock = $this->createMock(MonitoringStatusRepository::class);
        $this->notificationsManagerMock = $this->createMock(NotificationsManager::class);

        // create the monitoring manager instance
        $this->monitoringManager = new MonitoringManager(
            $this->appUtilMock,
            $this->cacheUtilMock,
            $this->logManager,
            $this->serverUtilMock,
            $this->emailManagerMock,
            $this->errorManagerMock,
            $this->serviceManagerMock,
            $this->notificationsManagerMock,
            $this->entityManagerMock,
            $this->repositoryMock
        );
    }

    /**
     * Test get service monitoring repository
     *
     * @return void
     */
    public function testGetMonitoringStatusRepository(): void
    {
        $search = ['service_name' => 'test_service'];
        $monitoringStatus = new MonitoringStatus();

        // mock repository
        $this->repositoryMock->expects($this->once())
            ->method('findOneBy')->with($search)->willReturn($monitoringStatus);

        // call method
        $result = $this->monitoringManager->getMonitoringStatusRepository($search);

        // assert result
        $this->assertSame($monitoringStatus, $result);
    }

    /**
     * Test set monitoring status for a new service
     *
     * @return void
     */
    public function testSetMonitoringStatusNewService(): void
    {
        $status = 'ok';
        $serviceName = 'test_service';
        $message = 'Service initialized';

        // mock repository
        $this->repositoryMock->expects($this->once())
            ->method('findOneBy')->with(['service_name' => $serviceName])->willReturn(null);

        // mock entity manager
        $this->entityManagerMock->expects($this->once())
            ->method('persist')->with($this->isInstanceOf(MonitoringStatus::class));
        $this->entityManagerMock->expects($this->once())->method('flush');

        // call method
        $this->monitoringManager->setMonitoringStatus($serviceName, $message, $status);
    }

    /**
     * Test set monitoring status for an existing service
     *
     * @return void
     */
    public function testSetMonitoringStatusExistingService(): void
    {
        $status = 'running';
        $message = 'Service running';
        $serviceName = 'test_service';

        // mock service monitoring entity
        $MonitoringStatus = $this->createMock(MonitoringStatus::class);

        // mock repository
        $this->repositoryMock->expects($this->once())
            ->method('findOneBy')->with(['service_name' => $serviceName])->willReturn($MonitoringStatus);

        // mock service monitoring entity
        $MonitoringStatus->expects($this->once())
            ->method('setMessage')->with($message)->willReturn($MonitoringStatus);
        $MonitoringStatus->expects($this->once())
            ->method('setStatus')->with($status)->willReturn($MonitoringStatus);
        $MonitoringStatus->expects($this->once())
            ->method('setLastUpdateTime')->with($this->isInstanceOf(\DateTime::class));

        // mock entity manager
        $this->entityManagerMock->expects($this->once())->method('flush');

        // call method
        $this->monitoringManager->setMonitoringStatus($serviceName, $message, $status);
    }

    /**
     * Test get monitoring status
     *
     * @return void
     */
    public function testGetMonitoringStatus(): void
    {
        $serviceName = 'test_service';
        $status = 'ok';
        $MonitoringStatus = $this->createMock(MonitoringStatus::class);

        // mock repository
        $this->repositoryMock->expects($this->once())
            ->method('findOneBy')->with(['service_name' => $serviceName])
            ->willReturn($MonitoringStatus);

        // mock service monitoring entity
        $MonitoringStatus->expects($this->once())->method('getStatus')->willReturn($status);

        // call method
        $result = $this->monitoringManager->getMonitoringStatus($serviceName);

        // assert result
        $this->assertSame($status, $result);
    }
}
