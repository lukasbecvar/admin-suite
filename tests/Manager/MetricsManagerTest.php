<?php

namespace App\Tests\Manager;

use DateTime;
use Exception;
use App\Util\AppUtil;
use App\Entity\Metric;
use App\Util\CacheUtil;
use Doctrine\ORM\Query;
use App\Manager\LogManager;
use Doctrine\DBAL\Statement;
use Doctrine\DBAL\Connection;
use App\Manager\ErrorManager;
use App\Util\VisitorInfoUtil;
use App\Entity\ServiceVisitor;
use Doctrine\ORM\QueryBuilder;
use App\Manager\MetricsManager;
use App\Manager\ServiceManager;
use PHPUnit\Framework\TestCase;
use App\Manager\DatabaseManager;
use Psr\Cache\CacheItemInterface;
use App\Repository\MetricRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\ServiceVisitorRepository;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

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
    private VisitorInfoUtil & MockObject $visitorInfoUtilMock;
    private DatabaseManager & MockObject $databaseManagerMock;
    private MetricRepository & MockObject $metricRepositoryMock;
    private EntityManagerInterface & MockObject $entityManagerMock;
    private ServiceVisitorRepository & MockObject $serviceVisitorRepositoryMock;

    protected function setUp(): void
    {
        // mock dependencies
        $this->appUtilMock = $this->createMock(AppUtil::class);
        $this->cacheUtilMock = $this->createMock(CacheUtil::class);
        $this->logManagerMock = $this->createMock(LogManager::class);
        $this->errorManagerMock = $this->createMock(ErrorManager::class);
        $this->serviceManagerMock = $this->createMock(ServiceManager::class);
        $this->visitorInfoUtilMock = $this->createMock(VisitorInfoUtil::class);
        $this->databaseManagerMock = $this->createMock(DatabaseManager::class);
        $this->metricRepositoryMock = $this->createMock(MetricRepository::class);
        $this->entityManagerMock = $this->createMock(EntityManagerInterface::class);
        $this->serviceVisitorRepositoryMock = $this->createMock(ServiceVisitorRepository::class);

        // init metrics manager instance
        $this->metricsManager = new MetricsManager(
            $this->appUtilMock,
            $this->cacheUtilMock,
            $this->logManagerMock,
            $this->errorManagerMock,
            $this->serviceManagerMock,
            $this->visitorInfoUtilMock,
            $this->databaseManagerMock,
            $this->metricRepositoryMock,
            $this->entityManagerMock,
            $this->serviceVisitorRepositoryMock
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
     * Test get raw metrics from cache
     *
     * @return void
     */
    public function testGetRawMetricsFromCache(): void
    {
        // mock service manager
        $this->serviceManagerMock->method('getServicesList')->willReturn([
            'host-system' => [
                'type' => 'http',
                'metrics_monitoring' => [
                    'collect_metrics' => true
                ]
            ]
        ]);

        // create mock cache items for raw values and times
        $cpuRawValuesCacheItem = $this->createMock(CacheItemInterface::class);
        $cpuRawValuesCacheItem->method('get')->willReturn([45, 50, 55]);
        $cpuRawTimesCacheItem = $this->createMock(CacheItemInterface::class);
        $cpuRawTimesCacheItem->method('get')->willReturn(['10:00', '10:01', '10:02']);
        $ramRawValuesCacheItem = $this->createMock(CacheItemInterface::class);
        $ramRawValuesCacheItem->method('get')->willReturn([40, 45, 50]);
        $ramRawTimesCacheItem = $this->createMock(CacheItemInterface::class);
        $ramRawTimesCacheItem->method('get')->willReturn(['10:00', '10:01', '10:02']);

        // mock cache util to return cache items
        $this->cacheUtilMock->method('isCatched')->willReturnMap([
            ['cpu_usage_host-system_raw_values', true],
            ['cpu_usage_host-system_raw_times', true],
            ['ram_usage_host-system_raw_values', true],
            ['ram_usage_host-system_raw_times', true],
            ['storage_usage_host-system_raw_values', false],
            ['storage_usage_host-system_raw_times', false],
            ['network_usage_host-system_raw_values', false],
            ['network_usage_host-system_raw_times', false]
        ]);

        $this->cacheUtilMock->method('getValue')->willReturnMap([
            ['cpu_usage_host-system_raw_values', $cpuRawValuesCacheItem],
            ['cpu_usage_host-system_raw_times', $cpuRawTimesCacheItem],
            ['ram_usage_host-system_raw_values', $ramRawValuesCacheItem],
            ['ram_usage_host-system_raw_times', $ramRawTimesCacheItem]
        ]);

        // call tested method
        $result = $this->metricsManager->getRawMetricsFromCache('host-system');

        // assert result
        $this->assertIsArray($result);
        $this->assertArrayHasKey('categories', $result);
        $this->assertArrayHasKey('metrics', $result);
        $this->assertArrayHasKey('cpu_usage', $result['metrics']);
        $this->assertArrayHasKey('ram_usage', $result['metrics']);
        $this->assertEquals(['10:00', '10:01', '10:02'], $result['categories']);
        $this->assertEquals(45, $result['metrics']['cpu_usage'][0]['value']);
        $this->assertEquals(50, $result['metrics']['cpu_usage'][1]['value']);
        $this->assertEquals(55, $result['metrics']['cpu_usage'][2]['value']);
        $this->assertEquals(40, $result['metrics']['ram_usage'][0]['value']);
        $this->assertEquals(45, $result['metrics']['ram_usage'][1]['value']);
        $this->assertEquals(50, $result['metrics']['ram_usage'][2]['value']);
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
     * Test save metric with cache summary
     *
     * @return void
     */
    public function testSaveMetricWithCacheSummary(): void
    {
        // testing data
        $metricName = 'cpu_usage';
        $value = 50.5;
        $serviceName = 'host-system';

        // mock environment values
        $this->appUtilMock->method('getEnvValue')->willReturnMap([
            ['MONITORING_INTERVAL', '1'],
            ['METRICS_SAVE_INTERVAL', '5']
        ]);

        // mock cache items
        $rawValuesCacheItem = $this->createMock(CacheItemInterface::class);
        $rawValuesCacheItem->method('get')->willReturn([45, 55]);
        $rawTimesCacheItem = $this->createMock(CacheItemInterface::class);
        $rawTimesCacheItem->method('get')->willReturn(['10:00:00', '10:01:00']);

        // mock cache util
        $this->cacheUtilMock->method('getValue')->willReturnMap([
            [$metricName . '_' . $serviceName . '_raw_values', $rawValuesCacheItem],
            [$metricName . '_' . $serviceName . '_raw_times', $rawTimesCacheItem]
        ]);
        $this->cacheUtilMock->method('isCatched')->willReturn(false);

        // expect setValue to be called for raw values and times
        $this->cacheUtilMock->expects($this->exactly(3))->method('setValue');

        // expect entity manager to be called
        $this->entityManagerMock->expects($this->once())->method('persist');
        $this->entityManagerMock->expects($this->once())->method('flush');

        // call tested method
        $this->metricsManager->saveMetricWithCacheSummary($metricName, $value, $serviceName);
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
        $this->cacheUtilMock->method('isCatched')->with($metricName . '_' . $serviceName . '_last_save_time')->willReturn(true);

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
        $this->appUtilMock->method('getEnvValue')->with('METRICS_SAVE_INTERVAL')->willReturn('10');

        // simulate value is not catched
        $this->cacheUtilMock->method('isCatched')->with($metricName . '_' . $serviceName . '_last_save_time')->willReturn(false);

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

    /**
     * Test group metrics by month
     *
     * @return void
     */
    public function testGroupMetricsByMonth(): void
    {
        // create mock metrics
        $metric1 = $this->createMock(Metric::class);
        $metric1->method('getServiceName')->willReturn('service1');
        $metric1->method('getName')->willReturn('cpu_usage');
        $metric1->method('getValue')->willReturn('50.5');
        $metric1->method('getTime')->willReturn(new DateTime('2023-01-15'));
        $metric2 = $this->createMock(Metric::class);
        $metric2->method('getServiceName')->willReturn('service1');
        $metric2->method('getName')->willReturn('cpu_usage');
        $metric2->method('getValue')->willReturn('60.2');
        $metric2->method('getTime')->willReturn(new DateTime('2023-01-20'));
        $metric3 = $this->createMock(Metric::class);
        $metric3->method('getServiceName')->willReturn('service2');
        $metric3->method('getName')->willReturn('ram_usage');
        $metric3->method('getValue')->willReturn('75.0');
        $metric3->method('getTime')->willReturn(new DateTime('2023-02-10'));

        $metrics = [$metric1, $metric2, $metric3];

        // call tested method
        $result = $this->metricsManager->groupMetricsByMonth($metrics);

        // assert result
        $this->assertIsArray($result);
        $this->assertCount(2, $result);

        // check first group (service1|cpu_usage|2023-01)
        $firstGroupKey = 'service1|cpu_usage|2023-01';
        $this->assertArrayHasKey($firstGroupKey, $result);
        $this->assertEquals('service1', $result[$firstGroupKey]['service_name']);
        $this->assertEquals('cpu_usage', $result[$firstGroupKey]['metric_name']);
        $this->assertEquals('2023-01', $result[$firstGroupKey]['month']);
        $this->assertEquals(2, $result[$firstGroupKey]['count']);
        $this->assertEquals(110.7, $result[$firstGroupKey]['sum']);
        $this->assertEquals([50.5, 60.2], $result[$firstGroupKey]['values']);

        // check second group (service2|ram_usage|2023-02)
        $secondGroupKey = 'service2|ram_usage|2023-02';
        $this->assertArrayHasKey($secondGroupKey, $result);
        $this->assertEquals('service2', $result[$secondGroupKey]['service_name']);
        $this->assertEquals('ram_usage', $result[$secondGroupKey]['metric_name']);
        $this->assertEquals('2023-02', $result[$secondGroupKey]['month']);
        $this->assertEquals(1, $result[$secondGroupKey]['count']);
        $this->assertEquals(75.0, $result[$secondGroupKey]['sum']);
        $this->assertEquals([75.0], $result[$secondGroupKey]['values']);
    }

    /**
     * Test group metrics by month with null time
     *
     * @return void
     */
    public function testGroupMetricsByMonthWithNullTime(): void
    {
        // create mock metric with null time
        $metric = $this->createMock(Metric::class);
        $metric->method('getServiceName')->willReturn('service1');
        $metric->method('getName')->willReturn('cpu_usage');
        $metric->method('getValue')->willReturn('50.5');
        $metric->method('getTime')->willReturn(null);

        $metrics = [$metric];

        // call tested method
        $result = $this->metricsManager->groupMetricsByMonth($metrics);

        // assert result is empty (metric with null time should be skipped)
        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * Test estimate space savings
     *
     * @return void
     */
    public function testEstimateSpaceSavings(): void
    {
        // create mock old metrics (10 metrics)
        $oldMetrics = [];
        for ($i = 0; $i < 10; $i++) {
            $oldMetrics[] = $this->createMock(Metric::class);
        }

        // create mock grouped metrics (2 groups)
        $groupedMetrics = [
            'group1' => ['service_name' => 'service1', 'metric_name' => 'cpu_usage'],
            'group2' => ['service_name' => 'service2', 'metric_name' => 'ram_usage']
        ];

        // call tested method
        $result = $this->metricsManager->estimateSpaceSavings($oldMetrics, $groupedMetrics);

        // assert result (10 old records * 100 bytes - 2 new records * 100 bytes = 800 bytes saved)
        $this->assertEquals(800, $result);
    }

    /**
     * Test estimate space savings when no savings
     *
     * @return void
     */
    public function testEstimateSpaceSavingsWhenNoSavings(): void
    {
        // create mock old metrics (1 metric)
        $oldMetrics = [$this->createMock(Metric::class)];

        // create mock grouped metrics (2 groups - more than old metrics)
        $groupedMetrics = [
            'group1' => ['service_name' => 'service1'],
            'group2' => ['service_name' => 'service2']
        ];

        // call tested method
        $result = $this->metricsManager->estimateSpaceSavings($oldMetrics, $groupedMetrics);

        // assert result is 0 (no negative savings)
        $this->assertEquals(0, $result);
    }

    /**
     * Test format aggregated metrics for display
     *
     * @return void
     */
    public function testFormatAggregatedMetricsForDisplay(): void
    {
        // create mock grouped metrics
        $groupedMetrics = [
            'service1|cpu_usage|2023-01' => [
                'service_name' => 'service1',
                'metric_name' => 'cpu_usage',
                'month' => '2023-01',
                'count' => 10,
                'sum' => 550.5
            ],
            'service2|ram_usage|2023-01' => [
                'service_name' => 'service2',
                'metric_name' => 'ram_usage',
                'month' => '2023-01',
                'count' => 5,
                'sum' => 375.0
            ],
            'service3|disk_usage|2023-02' => [
                'service_name' => 'service3',
                'metric_name' => 'disk_usage',
                'month' => '2023-02',
                'count' => 8,
                'sum' => 640.0
            ]
        ];

        // call tested method with limit
        $result = $this->metricsManager->formatAggregatedMetricsForDisplay($groupedMetrics, 2);

        // assert result
        $this->assertIsArray($result);
        $this->assertCount(2, $result); // limited to 2 records

        // check first record
        $this->assertEquals('service1', $result[0][0]);
        $this->assertEquals('cpu_usage', $result[0][1]);
        $this->assertEquals('2023-01', $result[0][2]);
        $this->assertEquals(10, $result[0][3]);
        $this->assertEquals(55.05, $result[0][4]);

        // check second record
        $this->assertEquals('service2', $result[1][0]);
        $this->assertEquals('ram_usage', $result[1][1]);
        $this->assertEquals('2023-01', $result[1][2]);
        $this->assertEquals(5, $result[1][3]);
        $this->assertEquals(75.0, $result[1][4]);
    }

    /**
     * Test format aggregated metrics for display with default limit
     *
     * @return void
     */
    public function testFormatAggregatedMetricsForDisplayWithDefaultLimit(): void
    {
        // create mock grouped metrics (just one for simplicity)
        $groupedMetrics = [
            'service1|cpu_usage|2023-01' => [
                'service_name' => 'service1',
                'metric_name' => 'cpu_usage',
                'month' => '2023-01',
                'count' => 4,
                'sum' => 200.0
            ]
        ];

        // call tested method without limit (should use default 20)
        $result = $this->metricsManager->formatAggregatedMetricsForDisplay($groupedMetrics);

        // assert result
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals(50.0, $result[0][4]);
    }

    /**
     * Test get aggregation preview
     *
     * @return void
     */
    public function testGetAggregationPreview(): void
    {
        $cutoffDate = new DateTime('-30 days');

        // mock old metrics
        $oldMetric = $this->createMock(Metric::class);
        $oldMetric->method('getServiceName')->willReturn('service1');
        $oldMetric->method('getName')->willReturn('cpu_usage');
        $oldMetric->method('getValue')->willReturn('50.0');
        $oldMetric->method('getTime')->willReturn(new DateTime('2023-01-15'));
        $oldMetrics = [$oldMetric];

        // mock recent metrics
        $recentMetrics = [
            ['name' => 'cpu_usage', 'value' => '60.0', 'service_name' => 'service1', 'time' => new DateTime()]
        ];

        // mock repository calls
        $this->metricRepositoryMock->expects($this->once())
            ->method('getOldMetrics')
            ->with($cutoffDate)
            ->willReturn($oldMetrics);
        $this->metricRepositoryMock->expects($this->once())
            ->method('getRecentMetrics')
            ->with($cutoffDate)
            ->willReturn($recentMetrics);

        // call tested method
        $result = $this->metricsManager->getAggregationPreview($cutoffDate);

        // assert result
        $this->assertIsArray($result);
        $this->assertArrayHasKey('old_metrics', $result);
        $this->assertArrayHasKey('recent_metrics', $result);
        $this->assertArrayHasKey('grouped_metrics', $result);
        $this->assertArrayHasKey('space_saved', $result);
        $this->assertEquals($oldMetrics, $result['old_metrics']);
        $this->assertEquals($recentMetrics, $result['recent_metrics']);
        $this->assertIsArray($result['grouped_metrics']);
        $this->assertIsInt($result['space_saved']);
    }

    /**
     * Test aggregate old metrics when no old metrics exist
     *
     * @return void
     */
    public function testAggregateOldMetricsWhenNoOldMetricsExist(): void
    {
        $cutoffDate = new DateTime('-30 days');

        // mock repository to return no old metrics
        $this->metricRepositoryMock->expects($this->once())
            ->method('getOldMetrics')
            ->with($cutoffDate)
            ->willReturn([]);

        // mock repository to return some recent metrics
        $recentMetrics = [
            ['name' => 'cpu_usage', 'value' => '60.0', 'service_name' => 'service1', 'time' => new DateTime()]
        ];
        $this->metricRepositoryMock->expects($this->once())
            ->method('getRecentMetrics')
            ->with($cutoffDate)
            ->willReturn($recentMetrics);

        // call tested method
        $result = $this->metricsManager->aggregateOldMetrics($cutoffDate);

        // assert result
        $this->assertIsArray($result);
        $this->assertEquals(0, $result['deleted']);
        $this->assertEquals(0, $result['created']);
        $this->assertEquals(1, $result['preserved']);
        $this->assertEquals(0, $result['space_saved']);
    }

    /**
     * Test aggregate old metrics success scenario (mocked)
     *
     * @return void
     */
    public function testAggregateOldMetricsSuccess(): void
    {
        $cutoffDate = new DateTime('-30 days');

        // create mock old metrics
        $oldMetric = $this->createMock(Metric::class);
        $oldMetric->method('getServiceName')->willReturn('service1');
        $oldMetric->method('getName')->willReturn('cpu_usage');
        $oldMetric->method('getValue')->willReturn('50.0');
        $oldMetric->method('getTime')->willReturn(new DateTime('2023-01-15'));
        $oldMetrics = [$oldMetric];

        // mock recent metrics
        $recentMetrics = [
            ['name' => 'cpu_usage', 'value' => '60.0', 'service_name' => 'service1', 'time' => new DateTime()]
        ];

        // mock repository calls
        $this->metricRepositoryMock->expects($this->once())
            ->method('getOldMetrics')
            ->with($cutoffDate)
            ->willReturn($oldMetrics);
        $this->metricRepositoryMock->expects($this->once())
            ->method('getRecentMetrics')
            ->with($cutoffDate)
            ->willReturn($recentMetrics);

        // mock entity manager transaction methods
        $this->entityManagerMock->expects($this->once())->method('beginTransaction');
        $this->entityManagerMock->expects($this->once())->method('commit');
        $this->entityManagerMock->expects($this->once())->method('flush');

        // mock query builder for delete operation
        $queryBuilderMock = $this->createMock(QueryBuilder::class);
        $queryMock = $this->createMock(Query::class);
        $queryBuilderMock->method('delete')->willReturnSelf();
        $queryBuilderMock->method('getQuery')->willReturn($queryMock);
        $queryMock->method('execute')->willReturn(1);
        $this->entityManagerMock->method('createQueryBuilder')->willReturn($queryBuilderMock);

        // expect persist calls for recent metrics and aggregated metrics
        $this->entityManagerMock->expects($this->exactly(2))->method('persist');

        // expect log call
        $this->logManagerMock->expects($this->once())->method('log')->with(
            'metrics-aggregation',
            $this->stringContains('Aggregated 1 old metrics into'),
            LogManager::LEVEL_INFO
        );

        // call tested method
        $result = $this->metricsManager->aggregateOldMetrics($cutoffDate);

        // assert result
        $this->assertIsArray($result);
        $this->assertEquals(1, $result['deleted']);
        $this->assertEquals(1, $result['created']);
        $this->assertEquals(1, $result['preserved']);
        $this->assertIsInt($result['space_saved']);
    }

    /**
     * Test register service visitor success
     *
     * @return void
     */
    public function testRegisterServiceVisitorSuccess(): void
    {
        // simulate service visitor not exists
        $serviceVisitorRepositoryMock = $this->createMock(ServiceVisitorRepository::class);
        $this->entityManagerMock->method('getRepository')->willReturn($serviceVisitorRepositoryMock);
        $serviceVisitorRepositoryMock->method('findOneBy')->willReturn(null);

        // expect persist and flush methods to be called
        $this->entityManagerMock->expects($this->once())->method('persist')->with($this->isInstanceOf(ServiceVisitor::class));
        $this->entityManagerMock->expects($this->once())->method('flush');

        // call tested method
        $this->metricsManager->registerServiceVisitor(
            serviceName: 'pied-piper.xyz',
            ipAddress: '127.0.0.5',
            location: 'New York',
            referer: 'https://pied-piper.xyz',
            userAgent: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/103.0.0.0 Safari/537.36'
        );
    }

    /**
     * Test register service visitor service visitor already exists
     *
     * @return void
     */
    public function testRegisterServiceVisitorServiceVisitorAlreadyExists(): void
    {
        // simulate service visitor already exists
        $serviceVisitorMock = $this->createMock(ServiceVisitor::class);
        $this->serviceVisitorRepositoryMock->method('findOneBy')->willReturn($serviceVisitorMock);

        // expect error handling
        $this->errorManagerMock->expects($this->once())->method('handleError')->with(
            'error to register service visitor: service visitor already exists',
            Response::HTTP_NOT_FOUND
        );

        // call tested method
        $this->metricsManager->registerServiceVisitor(
            serviceName: 'pied-piper.xyz',
            ipAddress: '127.0.0.5',
            location: 'New York',
            referer: 'https://pied-piper.xyz',
            userAgent: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/103.0.0.0 Safari/537.36'
        );
    }

    /**
     * Test register service visitor flush throws exception
     *
     * @return void
     */
    public function testRegisterServiceVisitorFlushThrowsException(): void
    {
        // simulate visitor not exists
        $serviceVisitorRepositoryMock = $this->createMock(ServiceVisitorRepository::class);
        $this->entityManagerMock->method('getRepository')->willReturn($serviceVisitorRepositoryMock);
        $serviceVisitorRepositoryMock->method('findOneBy')->willReturn(null);

        // expect persist and flush methods to be called
        $this->entityManagerMock->expects($this->once())->method('persist')->with($this->isInstanceOf(ServiceVisitor::class));

        // simulate flush throws exception
        $this->entityManagerMock->method('flush')->willThrowException(new Exception('DB error'));

        // expect error handling
        $this->errorManagerMock->expects($this->once())->method('handleError')->with(
            'error to register service visitor: DB error',
            Response::HTTP_INTERNAL_SERVER_ERROR
        );

        // call tested method
        $this->metricsManager->registerServiceVisitor(
            serviceName: 'pied-piper.xyz',
            ipAddress: '127.0.0.5',
            location: 'New York',
            referer: 'https://pied-piper.xyz',
            userAgent: 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/103.0.0.0 Safari/537.36'
        );
    }

    /**
     * Test check if visitor is already registered when found
     *
     * @return void
     */
    public function testCheckIfVisitorAlreadyRegisteredWhenFound(): void
    {
        // mock service visitor
        $serviceVisitorMock = $this->createMock(ServiceVisitor::class);
        $this->serviceVisitorRepositoryMock->method('findOneBy')->willReturn($serviceVisitorMock);

        // call tested method
        $result = $this->metricsManager->checkIfVisitorAlreadyRegistered('127.0.0.5', 'pied-piper.xyz');

        // assert result
        $this->assertTrue($result);
    }

    /**
     * Test check if visitor is already registered when found
     *
     * @return void
     */
    public function testCheckIfVisitorAlreadyRegisteredWhenNotFound(): void
    {
        // mock service visitor
        $this->serviceVisitorRepositoryMock->method('findOneBy')->willReturn(null);

        // call tested method
        $result = $this->metricsManager->checkIfVisitorAlreadyRegistered('127.0.0.5', 'pied-piper.xyz');

        // assert result
        $this->assertFalse($result);
    }

    /**
     * Test service visitors data
     *
     * @return void
     */
    public function testGetVisitorsByServiceName(): void
    {
        // call tested method
        $result = $this->metricsManager->getVisitorsByServiceName('pied-piper.xyz');

        // assert result
        $this->assertIsArray($result);
        $this->assertArrayHasKey('count', $result);
        $this->assertArrayHasKey('visitors', $result);
        $this->assertArrayHasKey('pagination', $result);
        $this->assertEquals(0, $result['count']);
        $this->assertCount(0, $result['visitors']);
        $this->assertNull($result['pagination']);
    }

    /**
     * Test get referers by service name
     *
     * @return void
     */
    public function testGetReferersByServiceName(): void
    {
        // call tested method
        $result = $this->metricsManager->getReferersByServiceName('pied-piper.xyz');

        // assert result
        $this->assertIsArray($result);
    }

    /**
     * Test update service visitor last visit time success
     *
     * @return void
     */
    public function testUpdateServiceVisitorLastVisitTimeSuccess(): void
    {
        // mock service visitor
        $serviceVisitorMock = $this->createMock(ServiceVisitor::class);
        $this->serviceVisitorRepositoryMock->method('findOneBy')->willReturn($serviceVisitorMock);
        $serviceVisitorMock->method('getId')->willReturn(1);

        // expect setLastVisitTime to be called
        $serviceVisitorMock->expects($this->once())->method('setLastVisitTime')->with($this->isInstanceOf(DateTime::class));

        // expect flush to be called
        $this->entityManagerMock->expects($this->once())->method('flush');

        // call tested method
        $this->metricsManager->updateServiceVisitorLastVisitTime('127.0.0.5', 'pied-piper.xyz');
    }

    /**
     * Test update service visitor last visit time service visitor not found
     *
     * @return void
     */
    public function testUpdateServiceVisitorLastVisitTimeServiceVisitorNotFound(): void
    {
        // simulate service visitor not exists
        $this->serviceVisitorRepositoryMock->method('findOneBy')->willReturn(null);

        // simulate error handling
        $this->errorManagerMock->method('handleError')->willThrowException(new HttpException(
            Response::HTTP_NOT_FOUND,
            'error to update service visitor last visit time: visitor not found'
        ));

        // expect exception
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('error to update service visitor last visit time: visitor not found');

        // call tested method
        $this->metricsManager->updateServiceVisitorLastVisitTime('123.123.123.123', 'not-found-service.xyz');
    }

    /**
     * Test update service visitor last visit time flush throws exception
     *
     * @return void
     */
    public function testUpdateServiceVisitorLastVisitTimeFlushThrowsException(): void
    {
        // mock service visitor
        $serviceVisitorMock = $this->createMock(ServiceVisitor::class);
        $this->serviceVisitorRepositoryMock->method('findOneBy')->willReturn($serviceVisitorMock);
        $serviceVisitorMock->method('getId')->willReturn(1);

        // expect setLastVisitTime to be called
        $serviceVisitorMock->expects($this->once())->method('setLastVisitTime')->with($this->isInstanceOf(DateTime::class));

        // simulate flush throws exception
        $this->entityManagerMock->expects($this->once())->method('flush')->willThrowException(new Exception('DB error'));

        // expect error handling
        $this->errorManagerMock->expects($this->once())->method('handleError')->with(
            'error to update service visitor last visit time: DB error',
            Response::HTTP_INTERNAL_SERVER_ERROR
        );

        // call tested method
        $this->metricsManager->updateServiceVisitorLastVisitTime('127.0.0.5', 'pied-piper.xyz');
    }

    /**
     * Test update service visitor user agent success
     *
     * @return void
     */
    public function testUpdateServiceVisitorUserAgentSuccess(): void
    {
        // mock service visitor
        $serviceVisitorMock = $this->createMock(ServiceVisitor::class);
        $this->serviceVisitorRepositoryMock->method('findOneBy')->willReturn($serviceVisitorMock);
        $serviceVisitorMock->method('getId')->willReturn(1);

        // expect setUserAgent to be called
        $serviceVisitorMock->expects($this->once())->method('setUserAgent')->with('new-user-agent');

        // expect flush to be called
        $this->entityManagerMock->expects($this->once())->method('flush');

        // call tested method
        $this->metricsManager->updateServiceVisitorUserAgent('127.0.0.5', 'pied-piper.xyz', 'new-user-agent');
    }

    /**
     * Test update service visitor user agent service visitor not found
     *
     * @return void
     */
    public function testUpdateServiceVisitorUserAgentServiceVisitorNotFound(): void
    {
        // simulate service visitor not exists
        $this->serviceVisitorRepositoryMock->method('findOneBy')->willReturn(null);

        // simulate error handling
        $this->errorManagerMock->method('handleError')->willThrowException(new HttpException(
            Response::HTTP_NOT_FOUND,
            'error to update service visitor user agent: visitor not found'
        ));

        // expect exception
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('error to update service visitor user agent: visitor not found');

        // call tested method
        $this->metricsManager->updateServiceVisitorUserAgent('123.123.123.123', 'not-found-service.xyz', 'new-user-agent');
    }

    /**
     * Test update service visitor user agent flush throws exception
     *
     * @return void
     */
    public function testUpdateServiceVisitorUserAgentFlushThrowsException(): void
    {
        // mock service visitor
        $serviceVisitorMock = $this->createMock(ServiceVisitor::class);
        $this->serviceVisitorRepositoryMock->method('findOneBy')->willReturn($serviceVisitorMock);
        $serviceVisitorMock->method('getId')->willReturn(1);

        // expect setUserAgent to be called
        $serviceVisitorMock->expects($this->once())->method('setUserAgent')->with('new-user-agent');

        // simulate flush throws exception
        $this->entityManagerMock->expects($this->once())->method('flush')->willThrowException(new Exception('DB error'));

        // expect error handling
        $this->errorManagerMock->expects($this->once())->method('handleError')->with(
            'error to update service visitor user agent: DB error',
            Response::HTTP_INTERNAL_SERVER_ERROR
        );

        // call tested method
        $this->metricsManager->updateServiceVisitorUserAgent('127.0.0.5', 'pied-piper.xyz', 'new-user-agent');
    }

    /**
     * Test update service visitor referer success
     *
     * @return void
     */
    public function testUpdateServiceVisitorRefererSuccess(): void
    {
        // mock service visitor
        $serviceVisitorMock = $this->createMock(ServiceVisitor::class);
        $this->serviceVisitorRepositoryMock->method('findOneBy')->willReturn($serviceVisitorMock);
        $serviceVisitorMock->method('getReferer')->willReturn('Unknown');

        // expect setReferer to be called
        $serviceVisitorMock->expects($this->once())->method('setReferer')->with('new-referer.com');

        // expect flush to be called
        $this->entityManagerMock->expects($this->once())->method('flush');

        $this->metricsManager->updateServiceVisitorReferer('127.0.0.5', 'pied-piper.xyz', 'new-referer.com');
    }

    /**
     * Test update service visitor referer does not overwrite known referer
     *
     * @return void
     */
    public function testUpdateServiceVisitorRefererDoesNotOverwriteKnownReferer(): void
    {
        // mock service visitor
        $serviceVisitorMock = $this->createMock(ServiceVisitor::class);
        $this->serviceVisitorRepositoryMock->method('findOneBy')->willReturn($serviceVisitorMock);
        $serviceVisitorMock->method('getReferer')->willReturn('http://existing-referer.com');

        // expect setReferer and flush not to be called
        $serviceVisitorMock->expects($this->never())->method('setReferer');
        $this->entityManagerMock->expects($this->never())->method('flush');

        // call tested method
        $this->metricsManager->updateServiceVisitorReferer('127.0.0.5', 'pied-piper.xyz', 'new-referer.com');
    }

    /**
     * Test update service visitor referer service visitor not found
     *
     * @return void
     */
    public function testUpdateServiceVisitorRefererServiceVisitorNotFound(): void
    {
        // simulate service visitor not exists
        $this->serviceVisitorRepositoryMock->method('findOneBy')->willReturn(null);

        // simulate error handling
        $this->errorManagerMock->method('handleError')->willThrowException(new HttpException(
            Response::HTTP_NOT_FOUND,
            'error to update service visitor referer: service visitor not found'
        ));

        // expect exception
        $this->expectException(HttpException::class);
        $this->expectExceptionMessage('error to update service visitor referer: service visitor not found');

        // call tested method
        $this->metricsManager->updateServiceVisitorReferer('123.123.123.123', 'not-found-service.xyz', 'new-referer.com');
    }

    /**
     * Test update service visitor referer flush throws exception
     *
     * @return void
     */
    public function testUpdateServiceVisitorRefererFlushThrowsException(): void
    {
        // mock service visitor
        $serviceVisitorMock = $this->createMock(ServiceVisitor::class);
        $this->serviceVisitorRepositoryMock->method('findOneBy')->willReturn($serviceVisitorMock);
        $serviceVisitorMock->method('getReferer')->willReturn('Unknown');

        // expect setReferer to be called
        $serviceVisitorMock->expects($this->once())->method('setReferer')->with('new-referer.com');

        // simulate flush throws exception
        $this->entityManagerMock->expects($this->once())->method('flush')->willThrowException(new Exception('DB error'));

        // expect error handling
        $this->errorManagerMock->expects($this->once())->method('handleError')->with(
            'error to update service visitor referer: DB error',
            Response::HTTP_INTERNAL_SERVER_ERROR
        );

        // call tested method
        $this->metricsManager->updateServiceVisitorReferer('127.0.0.5', 'pied-piper.xyz', 'new-referer.com');
    }

    /**
     * Test validate service visitors
     *
     * @return void
     */
    public function testValidateServiceVisitors(): void
    {
        // mock services list
        $this->serviceManagerMock->method('getServicesList')->willReturn([
            'service1' => [],
            'service2' => []
        ]);

        // mock visitors
        $visitor1 = $this->createMock(ServiceVisitor::class);
        $visitor1->method('getServiceName')->willReturn('service1');
        $visitor2 = $this->createMock(ServiceVisitor::class);
        $visitor2->method('getServiceName')->willReturn('orphaned_service');
        $this->serviceVisitorRepositoryMock->method('findAll')->willReturn([$visitor1, $visitor2]);

        // mock entity manager
        $this->entityManagerMock->expects($this->once())->method('remove')->with($visitor2);
        $this->entityManagerMock->expects($this->once())->method('flush');

        // mock database manager
        $tableName = 'service_visitor';
        $this->databaseManagerMock->method('getEntityTableName')->willReturn($tableName);
        $this->databaseManagerMock->expects($this->once())->method('recalculateTableIds')->with($tableName);

        // mock connection and statement for duplicate removal
        $connectionMock = $this->createMock(Connection::class);
        $statementMock = $this->createMock(Statement::class);
        $this->entityManagerMock->method('getConnection')->willReturn($connectionMock);
        $connectionMock->method('prepare')->willReturn($statementMock);
        $statementMock->method('executeStatement')->willReturn(1);

        // call the method
        $result = $this->metricsManager->validateServiceVisitors();

        // assert the result
        $this->assertEquals(['orphaned_removed' => 1, 'duplicates_removed' => 1], $result);
    }
}
