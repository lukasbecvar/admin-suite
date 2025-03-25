<?php

namespace App\Tests\Manager;

use Exception;
use App\Util\AppUtil;
use App\Entity\Metric;
use App\Util\CacheUtil;
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
            'error to save metric: Database error',
            Response::HTTP_INTERNAL_SERVER_ERROR
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
            'metrics-manager',
            'deleted metric: ' . $metricName . ' - ' . $serviceName,
            LogManager::LEVEL_WARNING
        );

        // expect recalculate table ids call
        $this->databaseManagerMock->expects($this->once())->method('recalculateTableIds');

        // call tested method
        $this->metricsManager->deleteMetric($metricName, $serviceName);
    }
}
