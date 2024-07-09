<?php

namespace App\Tests\Manager;

use App\Util\AppUtil;
use App\Util\ServerUtil;
use App\Manager\EmailManager;
use App\Manager\ErrorManager;
use PHPUnit\Framework\TestCase;
use App\Manager\ServiceManager;
use App\Entity\ServiceMonitoring;
use App\Manager\LogManager;
use App\Manager\MonitoringManager;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class MonitoringManagerTest
 *
 * Test for monitoring manager class
 *
 * @package App\Tests\Manager
 */
class MonitoringManagerTest extends TestCase
{
    /** @var AppUtil|MockObject */
    private AppUtil|MockObject $appUtilMock;

    /** @var LogManager|MockObject */
    private LogManager|MockObject $logManager;

    /** @var MonitoringManager */
    private MonitoringManager $monitoringManager;

    /** @var ServerUtil|MockObject */
    private ServerUtil|MockObject $serverUtilMock;

    /** @var EmailManager|MockObject */
    private EmailManager|MockObject $emailManagerMock;

    /** @var ErrorManager|MockObject */
    private ErrorManager|MockObject $errorManagerMock;

    /** @var EntityRepository<ServiceMonitoring>|MockObject */
    private EntityRepository|MockObject $repositoryMock;

    /** @var ServiceManager|MockObject */
    private ServiceManager|MockObject $serviceManagerMock;

    /** @var EntityManagerInterface|MockObject */
    private EntityManagerInterface|MockObject $entityManagerMock;

    protected function setUp(): void
    {
        $this->appUtilMock = $this->createMock(AppUtil::class);
        $this->logManager = $this->createMock(LogManager::class);
        $this->serverUtilMock = $this->createMock(ServerUtil::class);
        $this->emailManagerMock = $this->createMock(EmailManager::class);
        $this->errorManagerMock = $this->createMock(ErrorManager::class);
        $this->serviceManagerMock = $this->createMock(ServiceManager::class);
        $this->entityManagerMock = $this->createMock(EntityManagerInterface::class);
        $this->repositoryMock = $this->createMock(EntityRepository::class);

        $this->entityManagerMock->method('getRepository')->willReturn($this->repositoryMock);

        $this->monitoringManager = new MonitoringManager(
            $this->appUtilMock,
            $this->logManager,
            $this->serverUtilMock,
            $this->emailManagerMock,
            $this->errorManagerMock,
            $this->serviceManagerMock,
            $this->entityManagerMock
        );
    }

    /**
     * Test get service monitoring repository
     *
     * @return void
     */
    public function testGetServiceMonitoringRepository(): void
    {
        $search = ['service_name' => 'test_service'];
        $serviceMonitoring = new ServiceMonitoring();

        $this->repositoryMock->expects($this->once())
            ->method('findOneBy')
            ->with($search)
            ->willReturn($serviceMonitoring);

        // call method
        $result = $this->monitoringManager->getServiceMonitoringRepository($search);

        // assert result
        $this->assertSame($serviceMonitoring, $result);
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

        $this->repositoryMock->expects($this->once())
            ->method('findOneBy')
            ->with(['service_name' => $serviceName])
            ->willReturn(null);

        $this->entityManagerMock->expects($this->once())
            ->method('persist')
            ->with($this->isInstanceOf(ServiceMonitoring::class));

        $this->entityManagerMock->expects($this->once())
            ->method('flush');

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

        $serviceMonitoring = $this->createMock(ServiceMonitoring::class);

        $this->repositoryMock->expects($this->once())
            ->method('findOneBy')
            ->with(['service_name' => $serviceName])
            ->willReturn($serviceMonitoring);

        $serviceMonitoring->expects($this->once())
            ->method('setMessage')
            ->with($message)
            ->willReturn($serviceMonitoring);

        $serviceMonitoring->expects($this->once())
            ->method('setStatus')
            ->with($status)
            ->willReturn($serviceMonitoring);

        $serviceMonitoring->expects($this->once())
            ->method('setLastUpdateTime')
            ->with($this->isInstanceOf(\DateTime::class));

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
        $serviceMonitoring = $this->createMock(ServiceMonitoring::class);

        $this->repositoryMock->expects($this->once())
            ->method('findOneBy')
            ->with(['service_name' => $serviceName])
            ->willReturn($serviceMonitoring);

        $serviceMonitoring->expects($this->once())
            ->method('getStatus')
            ->willReturn($status);

        // call method
        $result = $this->monitoringManager->getMonitoringStatus($serviceName);

        // assert result
        $this->assertSame($status, $result);
    }
}
