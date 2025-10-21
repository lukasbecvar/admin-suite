<?php

namespace App\Tests\Manager;

use Exception;
use App\Util\AppUtil;
use App\Util\JsonUtil;
use App\Util\CacheUtil;
use App\Util\ServerUtil;
use App\Entity\SLAHistory;
use App\Manager\LogManager;
use App\Manager\EmailManager;
use App\Manager\ErrorManager;
use PHPUnit\Framework\TestCase;
use App\Manager\ServiceManager;
use App\Manager\MetricsManager;
use App\Entity\MonitoringStatus;
use Psr\Cache\CacheItemInterface;
use App\Manager\MonitoringManager;
use App\Manager\NotificationsManager;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\SLAHistoryRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\Attributes\CoversClass;
use App\Repository\MonitoringStatusRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class MonitoringManagerTest
 *
 * Test cases for monitoring manager
 *
 * @package App\Tests\Manager
 */
#[CoversClass(MonitoringManager::class)]
class MonitoringManagerTest extends TestCase
{
    private AppUtil & MockObject $appUtilMock;
    private JsonUtil & MockObject $jsonUtilMock;
    private MonitoringManager $monitoringManager;
    private CacheUtil & MockObject $cacheUtilMock;
    private LogManager & MockObject $logManagerMock;
    private ServerUtil & MockObject $serverUtilMock;
    private EmailManager & MockObject $emailManagerMock;
    private ErrorManager & MockObject $errorManagerMock;
    private SymfonyStyle & MockObject $symfonyStyleMock;
    private MetricsManager & MockObject $metricsManagerMock;
    private ServiceManager & MockObject $serviceManagerMock;
    private EntityManagerInterface & MockObject $entityManagerMock;
    private MonitoringStatusRepository & MockObject $repositoryMock;
    private SLAHistoryRepository & MockObject $slaHistoryRepositoryMock;
    private NotificationsManager & MockObject $notificationsManagerMock;

    protected function setUp(): void
    {
        // mock dependencies
        $this->appUtilMock = $this->createMock(AppUtil::class);
        $this->jsonUtilMock = $this->createMock(JsonUtil::class);
        $this->cacheUtilMock = $this->createMock(CacheUtil::class);
        $this->logManagerMock = $this->createMock(LogManager::class);
        $this->serverUtilMock = $this->createMock(ServerUtil::class);
        $this->emailManagerMock = $this->createMock(EmailManager::class);
        $this->errorManagerMock = $this->createMock(ErrorManager::class);
        $this->symfonyStyleMock = $this->createMock(SymfonyStyle::class);
        $this->metricsManagerMock = $this->createMock(MetricsManager::class);
        $this->serviceManagerMock = $this->createMock(ServiceManager::class);
        $this->entityManagerMock = $this->createMock(EntityManagerInterface::class);
        $this->repositoryMock = $this->createMock(MonitoringStatusRepository::class);
        $this->slaHistoryRepositoryMock = $this->createMock(SLAHistoryRepository::class);
        $this->notificationsManagerMock = $this->createMock(NotificationsManager::class);

        // create the monitoring manager instance
        $this->monitoringManager = new MonitoringManager(
            $this->appUtilMock,
            $this->jsonUtilMock,
            $this->cacheUtilMock,
            $this->logManagerMock,
            $this->serverUtilMock,
            $this->emailManagerMock,
            $this->errorManagerMock,
            $this->metricsManagerMock,
            $this->serviceManagerMock,
            $this->slaHistoryRepositoryMock,
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

        // expect findOneBy method call
        $this->repositoryMock->expects($this->once())
            ->method('findOneBy')->with($search)->willReturn($monitoringStatus);

        // call tested method
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

        // expect findOneBy method call
        $this->repositoryMock->expects($this->once())
            ->method('findOneBy')->with(['service_name' => $serviceName])->willReturn(null);

        // expect persist and flush methods to be called
        $this->entityManagerMock->expects($this->once())
            ->method('persist')->with($this->isInstanceOf(MonitoringStatus::class));
        $this->entityManagerMock->expects($this->once())->method('flush');

        // call tested method
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

        // expect findOneBy method call
        $this->repositoryMock->expects($this->once())
            ->method('findOneBy')->with(['service_name' => $serviceName])->willReturn($MonitoringStatus);

        // expect setter methods to be called
        $MonitoringStatus->expects($this->once())
            ->method('setMessage')->with($message)->willReturn($MonitoringStatus);
        $MonitoringStatus->expects($this->once())
            ->method('setStatus')->with($status)->willReturn($MonitoringStatus);
        $MonitoringStatus->expects($this->once())
            ->method('setLastUpdateTime')->with($this->isInstanceOf(\DateTime::class));

        // expect flush method to be called
        $this->entityManagerMock->expects($this->once())->method('flush');

        // call tested method
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
        $monitoringStatus = $this->createMock(MonitoringStatus::class);

        // expect findOneBy method call
        $this->repositoryMock->expects($this->once())
            ->method('findOneBy')->with(['service_name' => $serviceName])
            ->willReturn($monitoringStatus);

        // expect getStatus method call
        $monitoringStatus->expects($this->once())->method('getStatus')->willReturn($status);

        // call tested method
        $result = $this->monitoringManager->getMonitoringStatus($serviceName);

        // assert result
        $this->assertSame($status, $result);
    }

    /**
     * Test get monitoring status when service not found
     *
     * @return void
     */
    public function testGetMonitoringStatusWhenServiceNotFound(): void
    {
        $serviceName = 'test_service';

        // simulate service entity not found
        $this->repositoryMock->expects($this->once())->method('findOneBy')
            ->with(['service_name' => $serviceName])->willReturn(null);

        // call tested method
        $result = $this->monitoringManager->getMonitoringStatus($serviceName);

        // assert result
        $this->assertNull($result);
    }

    /**
     * Test handle monitoring status when status changed first time
     *
     * @return void
     */
    public function testHandleMonitoringStatusWhenStatusChangedFirstTime(): void
    {
        // testing data
        $serviceName = 'test-service';
        $message = 'Service is down';
        $currentStatus = 'down';

        // simulate previous status service status
        $cacheItemMock = $this->createMock(CacheItemInterface::class);
        $cacheItemMock->method('get')->willReturn('ok');
        $this->cacheUtilMock->method('getValue')->willReturn($cacheItemMock);

        // set new status to cache
        $this->cacheUtilMock->expects($this->once())->method('setValue')->with(
            'monitoring-status-' . $serviceName,
            $currentStatus
        );

        // call tested method
        $this->monitoringManager->handleMonitoringStatus($serviceName, $currentStatus, $message);
    }

    /**
     * Test handle monitoring status when status has not changed since the last handle
     *
     * @return void
     */
    public function testHandleMonitoringStatusWhenStatusHasNotChangedSinceTheLastHandle(): void
    {
        // testing data
        $serviceName = 'test-service';
        $message = 'Service is down';
        $currentStatus = 'down';

        // simulate previous status service status
        $cacheItemMock = $this->createMock(CacheItemInterface::class);
        $cacheItemMock->method('get')->willReturn('down');
        $this->cacheUtilMock->method('getValue')->willReturn($cacheItemMock);

        // set new status to cache
        $this->cacheUtilMock->expects($this->never())->method('setValue')->with(
            'monitoring-status-' . $serviceName,
            $currentStatus
        );

        // expect send notification
        $this->notificationsManagerMock->expects($this->once())->method('sendNotification');

        // expect call log
        $this->logManagerMock->expects($this->once())->method('log')->with(
            'monitoring',
            $serviceName . ' status: ' . $currentStatus . ' msg: ' . $message,
            LogManager::LEVEL_WARNING
        );

        // call tested method
        $this->monitoringManager->handleMonitoringStatus($serviceName, $currentStatus, $message);
    }

    /**
     * Test increase down time
     *
     * @return void
     */
    public function testIncreaseDownTime(): void
    {
        $serviceName = 'test_service';
        $minutes = 30;

        // mock service entity
        $monitoringStatusMock = $this->createMock(MonitoringStatus::class);
        $this->repositoryMock->expects($this->once())->method('findOneBy')
            ->with(['service_name' => $serviceName])->willReturn($monitoringStatusMock);

        // expect call increaseDownTime
        $monitoringStatusMock->expects($this->once())->method('increaseDownTime')->with($minutes);

        // expect call entity manager flush
        $this->entityManagerMock->expects($this->once())->method('flush');

        // call tested method
        $this->monitoringManager->increaseDownTime($serviceName, $minutes);
    }

    /**
     * Test increase down time when exception occurs
     *
     * @return void
     */
    public function testIncreaseDownTimeWhenExceptionOccurs(): void
    {
        $serviceName = 'test_service';
        $minutes = 30;

        // mock service entity
        $monitoringStatusMock = $this->createMock(MonitoringStatus::class);
        $this->repositoryMock->expects($this->once())->method('findOneBy')
            ->with(['service_name' => $serviceName])->willReturn($monitoringStatusMock);

        // mock the increaseDownTime method to throw an exception
        $monitoringStatusMock->expects($this->once())->method('increaseDownTime')->with($minutes)
            ->willThrowException(new Exception('Database error'));

        // expect call handleError
        $this->errorManagerMock->expects($this->once())->method('handleError')->with(
            'error to increase down time: Database error',
            Response::HTTP_INTERNAL_SERVER_ERROR
        );

        // call tested method
        $this->monitoringManager->increaseDownTime($serviceName, $minutes);
    }

    /**
     * Test get service mountly SLA when service found and has down time
     *
     * @return void
     */
    public function testGetServiceMountlySlaWhenServiceFoundAndHasDownTime(): void
    {
        $serviceName = 'test_service';
        $downTime = 1000;

        // mock service entity
        $monitoringStatusMock = $this->createMock(MonitoringStatus::class);
        $monitoringStatusMock->expects($this->once())->method('getDownTime')->willReturn($downTime);
        $this->repositoryMock->expects($this->once())->method('findOneBy')
            ->with(['service_name' => $serviceName])->willReturn($monitoringStatusMock);

        // call tested method
        $result = $this->monitoringManager->getServiceMountlySLA($serviceName);

        // calculate expected SLA value for assert
        $totalMinutesInMonth = 30.44 * 24 * 60;
        $expectedSLA = round((1 - ($downTime / $totalMinutesInMonth)) * 100, 2);

        // assert result
        $this->assertEquals($expectedSLA, $result);
    }

    /**
     * Test get service mountly SLA when service is not found in repository
     *
     * @return void
     */
    public function testGetServiceMountlySlaWhenServiceNotFound(): void
    {
        $serviceName = 'test_service';

        // simulate service entity not found
        $this->repositoryMock->expects($this->once())->method('findOneBy')
            ->with(['service_name' => $serviceName])->willReturn(null);

        // call tested method
        $result = $this->monitoringManager->getServiceMountlySLA($serviceName);

        // assert result
        $this->assertNull($result);
    }

    /**
     * Test get service mountly SLA when down time is null
     *
     * @return void
     */
    public function testGetServiceMountlySlaWhenDownTimeIsNull(): void
    {
        $serviceName = 'test_service';

        // mock service entity
        $monitoringStatusMock = $this->createMock(MonitoringStatus::class);
        $monitoringStatusMock->expects($this->once())->method('getDownTime')->willReturn(null);
        $this->repositoryMock->expects($this->once())->method('findOneBy')
            ->with(['service_name' => $serviceName])->willReturn($monitoringStatusMock);

        // expect handleError call
        $this->errorManagerMock->expects($this->once())->method('handleError')->with(
            'error to get service SLA: down time is null',
            Response::HTTP_INTERNAL_SERVER_ERROR
        );

        // call tested method
        $this->monitoringManager->getServiceMountlySLA($serviceName);
    }

    /**
     * Test get SLA history when data found
     *
     * @return void
     */
    public function testGetSlaHistoryWhenDataFound(): void
    {
        // mock SLA history data
        $slaHistoryMock1 = $this->createMock(SLAHistory::class);
        $slaHistoryMock1->expects($this->once())->method('getServiceName')->willReturn('test_service_1');
        $slaHistoryMock1->expects($this->once())->method('getSlaTimeframe')->willReturn('2025-01-01');
        $slaHistoryMock1->expects($this->once())->method('getSlaValue')->willReturn(99.9);
        $slaHistoryMock2 = $this->createMock(SLAHistory::class);
        $slaHistoryMock2->expects($this->once())->method('getServiceName')->willReturn('test_service_2');
        $slaHistoryMock2->expects($this->once())->method('getSlaTimeframe')->willReturn('2025-01-02');
        $slaHistoryMock2->expects($this->once())->method('getSlaValue')->willReturn(98.5);

        // mock repository to return testing data
        $this->slaHistoryRepositoryMock->expects($this->once())->method('findAll')
            ->willReturn([$slaHistoryMock1, $slaHistoryMock2]);

        // call tested method
        $result = $this->monitoringManager->getSLAHistory();

        // expected result
        $expectedData = [
            'test_service_1' => [
                '2025-01-01' => 99.9
            ],
            'test_service_2' => [
                '2025-01-02' => 98.5
            ]
        ];

        // assert result
        $this->assertEquals($expectedData, $result);
    }

    /**
     * Test get SLA history when no data found
     *
     * @return void
     */
    public function testGetSlaHistoryWhenNoDataFound(): void
    {
        // simulate empty history data
        $this->slaHistoryRepositoryMock->expects($this->once())->method('findAll')->willReturn([]);

        // call tested method
        $result = $this->monitoringManager->getSLAHistory();

        // expected result
        $this->assertEquals([], $result);
    }

    /**
     * Test get SLA history when exception thrown
     *
     * @return void
     */
    public function testGetSlaHistoryWhenExceptionThrown(): void
    {
        // simulate exception thrown
        $this->slaHistoryRepositoryMock->expects($this->once())->method('findAll')
            ->willThrowException(new Exception('Database error'));

        // expect handleError call
        $this->errorManagerMock->expects($this->once())->method('handleError')->with(
            'error to get SLA history: Database error',
            Response::HTTP_INTERNAL_SERVER_ERROR
        );

        // call tested method
        $this->monitoringManager->getSLAHistory();
    }

    /**
     * Test save SLA history
     *
     * @return void
     */
    public function testSaveSLAHistory(): void
    {
        // testing data
        $serviceName = 'test_service';
        $slaTimeframe = '2025-01-01';
        $slaValue = 99.9;

        // expect entity manager call persist and flush
        $this->entityManagerMock->expects($this->once())->method('persist')->with(
            $this->isInstanceOf(SLAHistory::class)
        );
        $this->entityManagerMock->expects($this->once())->method('flush');

        // call tested method
        $this->monitoringManager->saveSLAHistory($serviceName, $slaTimeframe, $slaValue);
    }

    /**
     * Test save SLA history when exception thrown
     *
     * @return void
     */
    public function testSaveSLAHistoryWhenExceptionThrown(): void
    {
        // testing data
        $serviceName = 'test_service';
        $slaTimeframe = '2025-01-01';
        $slaValue = 99.9;

        // expect entity manager call persist
        $this->entityManagerMock->expects($this->once())->method('persist')->with(
            $this->isInstanceOf(SLAHistory::class)
        );

        // mock exception thrown
        $this->entityManagerMock->expects($this->once())->method('flush')
            ->willThrowException(new Exception('Database error'));

        // expect handleError call
        $this->errorManagerMock->expects($this->once())->method('handleError')->with(
            'error to save SLA history: Database error',
            Response::HTTP_INTERNAL_SERVER_ERROR
        );

        // call the method and expect it to handle the error
        $this->monitoringManager->saveSLAHistory($serviceName, $slaTimeframe, $slaValue);
    }

    /**
     * Test reset down times when no services in non current timeframe
     *
     * @return void
     */
    public function testResetDownTimesWhenNoServicesInNonCurrentTimeframe(): void
    {
        // simulate find services in non-current timeframe
        $this->repositoryMock->expects($this->once())->method('findByNonCurrentTimeframe')->willReturn([]);

        // expect flush to not be called
        $this->entityManagerMock->expects($this->never())->method('flush');

        // call tested method
        $this->monitoringManager->resetDownTimes($this->symfonyStyleMock);
    }

    /**
     * Test reset down times when there are services in non-current timeframe
     *
     * @return void
     */
    public function testResetDownTimesWhenServicesInNonCurrentTimeframe(): void
    {
        // mock testing entity
        $monitoringStatusMock = $this->createMock(MonitoringStatus::class);
        $monitoringStatusMock->expects($this->exactly(4))->method('getServiceName')->willReturn('test-service');
        $monitoringStatusMock->expects($this->exactly(2))->method('getSlaTimeframe')->willReturn('2024-12');
        $monitoringStatusMock->expects($this->exactly(1))->method('setSlaTimeframe')->with(date('Y-m'));
        $monitoringStatusMock->expects($this->exactly(1))->method('setDownTime')->with(0);
        $monitoringStatusMock->expects($this->exactly(1))->method('getDownTime')->willReturn(3);
        $this->repositoryMock->method('findOneBy')->with(['service_name' => 'test-service'])
            ->willReturn($monitoringStatusMock);
        $this->repositoryMock->expects($this->once())->method('findByNonCurrentTimeframe')
            ->willReturn([$monitoringStatusMock]);

        // expect log to be called
        $this->logManagerMock->expects($this->once())->method('log')->with(
            'SLA timeframe reset',
            'test-service SLA for timeframe 2024-12 is: 99.99%',
            LogManager::LEVEL_INFO
        );

        // call tested method
        $this->monitoringManager->resetDownTimes($this->symfonyStyleMock);
    }

    /**
     * Test handle database down
     *
     * @return void
     */
    public function testHandleDatabaseDown(): void
    {
        // expect console output
        $this->symfonyStyleMock->expects($this->once())->method('writeln')->with(
            $this->matchesRegularExpression('/\[\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}\] monitoring: <fg=red>database is down<\/fg=red>/')
        );

        // call tested method
        $this->monitoringManager->handleDatabaseDown($this->symfonyStyleMock, true);
    }

    /**
     * Test temporary disable monitoring
     *
     * @return void
     */
    public function testTemporaryDisableMonitoring(): void
    {
        // mock services list
        $this->serviceManagerMock->expects($this->once())->method('getServicesList')->willReturn([
            'test_service' => [
                'monitoring' => true,
                'type' => 'systemd',
                'display_name' => 'Test service',
                'url' => 'http://test.com',
                'metrics_monitoring' => [
                    'collect_metrics' => true,
                    'metrics_collector_url' => 'http://test.com/metrics'
                ]
            ]
        ]);

        // expect cache set
        $this->cacheUtilMock->expects($this->once())->method('setValue')->with(
            'monitoring-temporary-disabled-test_service',
            'disabled',
            60
        );

        // call tested method
        $this->monitoringManager->temporaryDisableMonitoring('test_service', 1);
    }

    /**
     * Test check if monitoring disabled when found in cache
     *
     * @return void
     */
    public function testIsMonitoringTemporarilyDisabled(): void
    {
        // mock cache
        $this->cacheUtilMock->method('isCatched')->willReturn(true);

        // call tested method
        $result = $this->monitoringManager->isMonitoringTemporarilyDisabled('test_service');

        // assert result
        $this->assertTrue($result);
    }

    /**
     * Test check if monitoring disabled when not found in cache
     *
     * @return void
     */
    public function testIsMonitoringNotTemporarilyDisabled(): void
    {
        // mock cache
        $this->cacheUtilMock->method('isCatched')->willReturn(false);

        // call tested method
        $result = $this->monitoringManager->isMonitoringTemporarilyDisabled('test_service');

        // assert result
        $this->assertFalse($result);
    }

    /**
     * Test handle database down when database is down for the first time
     *
     * @return void
     */
    public function testHandleDatabaseDownWhenDatabaseIsDownForTheFirstTime(): void
    {
        // Expect email to be sent
        $this->emailManagerMock->expects($this->once())->method('sendMonitoringStatusEmail')
            ->with($this->anything(), 'Mysql', 'Mysql server is down');

        // Expect console output
        $this->symfonyStyleMock->expects($this->once())->method('writeln')
            ->with($this->stringContains('database is down'));

        // Call the method
        $this->monitoringManager->handleDatabaseDown($this->symfonyStyleMock, false);
    }

    /**
     * Test save SLA history with specific values
     *
     * @return void
     */
    public function testSaveSLAHistoryWithSpecificValues(): void
    {
        // testing data
        $serviceName = 'nginx';
        $slaTimeframe = '2023-01';
        $slaValue = 99.95;

        // expect entity manager to persist with correct values
        $this->entityManagerMock->expects($this->once())->method('persist')->with($this->callback(function (SLAHistory $slaHistory) use ($serviceName, $slaTimeframe, $slaValue) {
            return $slaHistory->getServiceName() === $serviceName
                && $slaHistory->getSlaTimeframe() === $slaTimeframe
                && $slaHistory->getSlaValue() === $slaValue;
        }));

        // expect entity manager flush call
        $this->entityManagerMock->expects($this->once())->method('flush');

        // call tested method
        $this->monitoringManager->saveSLAHistory($serviceName, $slaTimeframe, $slaValue);
    }

    /**
     * Test temporary disable monitoring with non-existent service
     *
     * @return void
     */
    public function testTemporaryDisableMonitoringWithNonExistentService(): void
    {
        // mock services list
        $this->serviceManagerMock->expects($this->once())->method('getServicesList')->willReturn([
            'nginx' => [
                'monitoring' => true
            ]
        ]);

        // expect error handler to be called
        $this->errorManagerMock->expects($this->once())->method('handleError')->with(
            'error to disable monitoring: service non-existent-service not found',
            Response::HTTP_INTERNAL_SERVER_ERROR
        );

        // call tested method
        $this->monitoringManager->temporaryDisableMonitoring('non-existent-service', 1);
    }
}
