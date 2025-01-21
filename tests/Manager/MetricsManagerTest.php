<?php

namespace App\Tests\Manager;

use DateTime;
use Exception;
use App\Util\AppUtil;
use App\Entity\Metric;
use App\Util\CacheUtil;
use App\Util\ServerUtil;
use App\Manager\LogManager;
use App\Manager\ErrorManager;
use App\Manager\MetricsManager;
use App\Manager\ServiceManager;
use PHPUnit\Framework\TestCase;
use App\Manager\DatabaseManager;
use App\Repository\MetricRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class MetricsManagerTest
 *
 * Test cases for metrics manager
 *
 * @package App\Tests\Manager
 */
class MetricsManagerTest extends TestCase
{
    private MetricsManager $metricsManager;
    private AppUtil & MockObject $appUtilMock;
    private CacheUtil & MockObject $cacheUtilMock;
    private ServerUtil & MockObject $serverUtilMock;
    private LogManager & MockObject $logManagerMock;
    private ErrorManager & MockObject $errorManagerMock;
    private ServiceManager & MockObject $serviceManagerMock;
    private DatabaseManager & MockObject $databaseManagerMock;
    private MetricRepository & MockObject $metricRepositoryMock;
    private EntityManagerInterface & MockObject $entityManagerMock;

    protected function setUp(): void
    {
        // mock dependencies
        $this->appUtilMock = $this->createMock(AppUtil::class);
        $this->cacheUtilMock = $this->createMock(CacheUtil::class);
        $this->serverUtilMock = $this->createMock(ServerUtil::class);
        $this->logManagerMock = $this->createMock(LogManager::class);
        $this->errorManagerMock = $this->createMock(ErrorManager::class);
        $this->serviceManagerMock = $this->createMock(ServiceManager::class);
        $this->databaseManagerMock = $this->createMock(DatabaseManager::class);
        $this->metricRepositoryMock = $this->createMock(MetricRepository::class);
        $this->entityManagerMock = $this->createMock(EntityManagerInterface::class);

        // init metrics manager instance
        $this->metricsManager = new MetricsManager(
            $this->appUtilMock,
            $this->cacheUtilMock,
            $this->serverUtilMock,
            $this->logManagerMock,
            $this->errorManagerMock,
            $this->serviceManagerMock,
            $this->databaseManagerMock,
            $this->metricRepositoryMock,
            $this->entityManagerMock
        );
    }

    /**
     * Test get all services metrics
     *
     * @return void
     */
    public function testGetAllServicesMetrics(): void
    {
        // mock get services config
        $this->serviceManagerMock->method('getServicesList')->willReturn([
            'becvar.xyz' => [
                'service_name' => 'becvar.xyz',
                'type' => 'http',
                'monitoring' => true,
                'metrics_monitoring' => [
                    'collect_metrics' => true
                ]
            ],
            'paste.becvar.xyz' => [
                'service_name' => 'paste.becvar.xyz',
                'type' => 'http',
                'monitoring' => true,
                'metrics_monitoring' => [
                    'collect_metrics' => false
                ]
            ]
        ]);

        // call tested method
        $result = $this->metricsManager->getAllServicesMetrics('last_24_hours');

        // assert result
        $this->assertIsArray($result);
        $this->assertArrayHasKey('becvar.xyz', $result);
        $this->assertArrayHasKey('categories', $result['becvar.xyz']);
        $this->assertArrayHasKey('metrics', $result['becvar.xyz']);
    }

    /**
     * Test get service metrics
     *
     * @return void
     */
    public function testGetServiceMetrics(): void
    {
        // mock get services config
        $this->serviceManagerMock->method('getServicesList')->willReturn([
            'service1' => [
                'type' => 'http',
                'metrics_monitoring' => [
                    'collect_metrics' => true
                ]
            ],
            'service2' => [
                'type' => 'http',
                'metrics_monitoring' => [
                    'collect_metrics' => false
                ]
            ]
        ]);

        // call tested method
        $result = $this->metricsManager->getServiceMetrics('service1', 'last_24_hours');

        // assert result
        $this->assertIsArray($result);
        $this->assertArrayHasKey('categories', $result);
        $this->assertArrayHasKey('metrics', $result);
    }

    /**
     * Test get resource usage metrics
     *
     * @return void
     */
    public function testGetResourceUsageMetrics(): void
    {
        // mock testing metrics data
        $cpuMetric = $this->createMock(Metric::class);
        $cpuMetric->method('getTime')->willReturn(new DateTime('2024-11-08 12:00'));
        $cpuMetric->method('getValue')->willReturn('45.5');
        $cpuMetric2 = $this->createMock(Metric::class);
        $cpuMetric2->method('getTime')->willReturn(new DateTime('2024-11-08 13:00'));
        $cpuMetric2->method('getValue')->willReturn('47.0');
        $ramMetric = $this->createMock(Metric::class);
        $ramMetric->method('getTime')->willReturn(new DateTime('2024-11-08 12:00'));
        $ramMetric->method('getValue')->willReturn('65.0');
        $ramMetric2 = $this->createMock(Metric::class);
        $ramMetric2->method('getTime')->willReturn(new DateTime('2024-11-08 13:00'));
        $ramMetric2->method('getValue')->willReturn('66.5');
        $storageMetric = $this->createMock(Metric::class);
        $storageMetric->method('getTime')->willReturn(new DateTime('2024-11-08 12:00'));
        $storageMetric->method('getValue')->willReturn('80.0');
        $storageMetric2 = $this->createMock(Metric::class);
        $storageMetric2->method('getTime')->willReturn(new DateTime('2024-11-08 13:00'));
        $storageMetric2->method('getValue')->willReturn('85.0');
        $this->metricRepositoryMock->method('getMetricsByNameAndTimePeriod')->willReturnOnConsecutiveCalls(
            [$cpuMetric, $cpuMetric2],
            [$ramMetric, $ramMetric2],
            [$storageMetric, $storageMetric2]
        );

        // mock server util for get current usages
        $this->serverUtilMock->method('getCpuUsage')->willReturn(50.0);
        $this->serverUtilMock->method('getRamUsagePercentage')->willReturn(70);
        $this->serverUtilMock->method('getDriveUsagePercentage')->willReturn('60');

        // call tested method
        $metrics = $this->metricsManager->getResourceUsageMetrics('last_24_hours');

        // assert result
        $this->assertIsArray($metrics);
        $this->assertArrayHasKey('categories', $metrics);
        $this->assertArrayHasKey('cpu', $metrics);
        $this->assertArrayHasKey('ram', $metrics);
        $this->assertArrayHasKey('storage', $metrics);
        $this->assertEquals(['45.5', '47.0'], $metrics['cpu']['data']);
        $this->assertEquals(['65.0', '66.5'], $metrics['ram']['data']);
        $this->assertEquals(['80.0', '85.0'], $metrics['storage']['data']);
    }

    /**
     * Test save metrics success
     *
     * @return void
     */
    public function testSaveMetricSuccess(): void
    {
        // testing data
        $metricName = 'cpu_usage';
        $value = '50.5';

        // expect persist and flush methods to be called
        $this->entityManagerMock->expects($this->once())->method('persist')->with($this->isInstanceOf(Metric::class));
        $this->entityManagerMock->expects($this->once())->method('flush');

        // call tested method
        $this->metricsManager->saveMetric($metricName, $value);
    }

    /**
     * Test save metric when flush throws exception
     *
     * @return void
     */
    public function testSaveMetricWhenFlushThrowsException(): void
    {
        $metricName = 'cpu_usage';
        $value = '50.5';

        // mock entity manager
        $this->entityManagerMock->expects($this->once())->method('persist')->with($this->isInstanceOf(Metric::class));
        $this->entityManagerMock->expects($this->once())->method('flush')->willThrowException(new Exception('Database error'));

        // expect error handling
        $this->errorManagerMock->expects($this->once())->method('handleError')->with(
            message: 'error to save metric: Database error',
            code: Response::HTTP_INTERNAL_SERVER_ERROR
        );

        // call tested method
        $this->metricsManager->saveMetric($metricName, $value);
    }

    /**
     * Test save service metric when metric is already cached
     *
     * @return void
     */
    public function testSaveServiceMetricSkipsIfAlreadyCached(): void
    {
        // testing data
        $value = 256;
        $metricName = 'memory_usage';
        $serviceName = 'web-service';

        // simulate METRICS_SAVE_INTERVAL env value
        $this->appUtilMock->method('getEnvValue')->with('METRICS_SAVE_INTERVAL')->willReturn('10');

        // simulate value is catched
        $this->cacheUtilMock->method('isCatched')
            ->with($metricName . '_' . $serviceName . '_last_save_time')->willReturn(true);

        // expect entity manager not to be flushed (skipped)
        $this->entityManagerMock->expects($this->never())->method('flush');

        // call tested method
        $this->metricsManager->saveServiceMetric($metricName, $value, $serviceName);
    }

    /**
     * Test save service metric when metric is not cached
     *
     * @return void
     */
    public function testSaveServiceMetricSavesIfNotCached(): void
    {
        // testing data
        $value = 500;
        $metricName = 'disk_usage';
        $serviceName = 'db-service';

        // simulate METRICS_SAVE_INTERVAL env value
        $this->appUtilMock->method('getEnvValue')->with('METRICS_SAVE_INTERVAL')
            ->willReturn('10');

        // simulate value is not catched
        $this->cacheUtilMock->method('isCatched')->with($metricName . '_' . $serviceName . '_last_save_time')
            ->willReturn(false);

        // expect save value to cache call
        $this->cacheUtilMock->expects($this->once())->method('setValue');

        // expect entity manager to be flushed
        $this->entityManagerMock->expects($this->once())->method('flush');

        // call tested method
        $this->metricsManager->saveServiceMetric($metricName, $value, $serviceName);
    }

    /**
     * Test delete metric
     *
     * @return void
     */
    public function testDeleteMetric(): void
    {
        // mock metrics repository
        $metricName = 'test_metric';
        $serviceName = 'test_service';
        $metricEntityMock = $this->createMock(Metric::class);
        $this->metricRepositoryMock->expects($this->once())->method('findMetricsByNameAndService')
            ->with($metricName, $serviceName)->willReturn([$metricEntityMock]);

        // expect entity manager calls
        $this->entityManagerMock->expects($this->once())->method('remove');
        $this->entityManagerMock->expects($this->once())->method('flush');

        // expect log call
        $this->logManagerMock->expects($this->once())->method('log')->with(
            name: 'metrics-manager',
            message: 'deleted metric: ' . $metricName . ' - ' . $serviceName,
            level: LogManager::LEVEL_WARNING
        );

        // expect recalculate table ids call
        $this->databaseManagerMock->expects($this->once())->method('recalculateTableIds');

        // call tested method
        $this->metricsManager->deleteMetric($metricName, $serviceName);
    }
}
