<?php

namespace App\Tests\Manager;

use DateTime;
use Exception;
use App\Util\AppUtil;
use App\Entity\Metric;
use App\Util\CacheUtil;
use App\Util\ServerUtil;
use App\Manager\ErrorManager;
use App\Manager\MetricsManager;
use PHPUnit\Framework\TestCase;
use App\Repository\MetricRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Class MetricsManagerTest
 *
 * Test for metrics manager class
 *
 * @package App\Tests\Manager
 */
class MetricsManagerTest extends TestCase
{
    private MetricsManager $metricsManager;
    private AppUtil & MockObject $appUtilMock;
    private CacheUtil & MockObject $cacheUtilMock;
    private ServerUtil & MockObject $serverUtilMock;
    private ErrorManager & MockObject $errorManagerMock;
    private MetricRepository & MockObject $metricRepositoryMock;
    private EntityManagerInterface & MockObject $entityManagerMock;

    protected function setUp(): void
    {
        // mock dependencies
        $this->appUtilMock = $this->createMock(AppUtil::class);
        $this->cacheUtilMock = $this->createMock(CacheUtil::class);
        $this->serverUtilMock = $this->createMock(ServerUtil::class);
        $this->errorManagerMock = $this->createMock(ErrorManager::class);
        $this->metricRepositoryMock = $this->createMock(MetricRepository::class);
        $this->entityManagerMock = $this->createMock(EntityManagerInterface::class);

        // instantiate manager with mocked dependencies
        $this->metricsManager = new MetricsManager(
            $this->appUtilMock,
            $this->cacheUtilMock,
            $this->serverUtilMock,
            $this->errorManagerMock,
            $this->metricRepositoryMock,
            $this->entityManagerMock
        );
    }

    /**
     * Test getMetrics for last_24_hours period
     *
     * @return void
     */
    public function testGetMetricsSuccess(): void
    {
        // mock the MetricRepository to return objects instead of arrays for non-aggregate data
        $cpuMetric = $this->createMock(Metric::class);
        $cpuMetric->method('getTime')->willReturn(new DateTime('2024-11-08 12:00'));
        $cpuMetric->method('getValue')->willReturn('45.5');

        // mock the MetricRepository to return objects instead of arrays for aggregate data
        $cpuMetric2 = $this->createMock(Metric::class);
        $cpuMetric2->method('getTime')->willReturn(new DateTime('2024-11-08 13:00'));
        $cpuMetric2->method('getValue')->willReturn('47.0');

        // mock the MetricRepository to return objects instead of arrays for aggregate data
        $ramMetric = $this->createMock(Metric::class);
        $ramMetric->method('getTime')->willReturn(new DateTime('2024-11-08 12:00'));
        $ramMetric->method('getValue')->willReturn('65.0');

        // mock the MetricRepository to return objects instead of arrays for aggregate data
        $ramMetric2 = $this->createMock(Metric::class);
        $ramMetric2->method('getTime')->willReturn(new DateTime('2024-11-08 13:00'));
        $ramMetric2->method('getValue')->willReturn('66.5');

        // mock the MetricRepository to return objects instead of arrays for aggregate data
        $storageMetric = $this->createMock(Metric::class);
        $storageMetric->method('getTime')->willReturn(new DateTime('2024-11-08 12:00'));
        $storageMetric->method('getValue')->willReturn('80.0');

        // mock the MetricRepository to return objects instead of arrays for aggregate data
        $storageMetric2 = $this->createMock(Metric::class);
        $storageMetric2->method('getTime')->willReturn(new DateTime('2024-11-08 13:00'));
        $storageMetric2->method('getValue')->willReturn('85.0');

        // mocking repository to return Metric objects
        $this->metricRepositoryMock->method('getMetricsByNameAndTimePeriod')->willReturnOnConsecutiveCalls(
            [$cpuMetric, $cpuMetric2],
            [$ramMetric, $ramMetric2],
            [$storageMetric, $storageMetric2]
        );

        // mocking server utilization
        $this->serverUtilMock->method('getCpuUsage')->willReturn(50.0);
        $this->serverUtilMock->method('getRamUsagePercentage')->willReturn(70);
        $this->serverUtilMock->method('getDriveUsagePercentage')->willReturn('60');

        /**
         * @var array{
         *     categories: array<mixed>,
         *     cpu: array{data: array<string>},
         *     ram: array{data: array<string>},
         *     storage: array{data: array<string>}
         * }
         */
        $metrics = $this->metricsManager->getMetrics('last_24_hours');

        // assert the structure of returned data
        $this->assertArrayHasKey('categories', $metrics);
        $this->assertArrayHasKey('cpu', $metrics);
        $this->assertArrayHasKey('ram', $metrics);
        $this->assertArrayHasKey('storage', $metrics);

        // assert data values
        $this->assertEquals(['45.5', '47.0'], $metrics['cpu']['data']);
        $this->assertEquals(['65.0', '66.5'], $metrics['ram']['data']);
        $this->assertEquals(['80.0', '85.0'], $metrics['storage']['data']);
    }

    /**
     * Test handleError on getMetrics
     *
     * @return void
     */
    public function testHandleErrorOnGetMetrics(): void
    {
        // mock error handler to simulate an error
        $this->errorManagerMock->expects($this->once())->method('handleError')->with(
            $this->equalTo('error to get metrics: return data is not iterable'),
            $this->equalTo(Response::HTTP_INTERNAL_SERVER_ERROR)
        )->willThrowException(new HttpException(Response::HTTP_INTERNAL_SERVER_ERROR, 'Mock error'));

        // mocking the MetricRepository to return non-iterable data
        $this->metricRepositoryMock->method('getMetricsByNameAndTimePeriod')->willReturn(null);

        // expect exception
        $this->expectException(HttpException::class);

        // call tested method
        $this->metricsManager->getMetrics('last_24_hours');
    }

    /**
     * Test save metrics success
     *
     * @return void
     */
    public function testSaveMetricSuccess(): void
    {
        $metricName = 'cpu_usage';
        $value = '50.5';

        // mock entity manager
        $this->entityManagerMock->expects($this->once())->method('persist')->with($this->isInstanceOf(Metric::class));
        $this->entityManagerMock->expects($this->once())->method('flush');

        // call tested method
        $this->metricsManager->saveMetric($metricName, $value);
    }

    /**
     * Test save metric failure
     *
     * @return void
     */
    public function testSaveMetricFailure(): void
    {
        $metricName = 'cpu_usage';
        $value = '50.5';

        // mock entity manager
        $this->entityManagerMock->expects($this->once())->method('persist')->with($this->isInstanceOf(Metric::class));
        $this->entityManagerMock->expects($this->once())->method('flush')->willThrowException(new Exception('Database error'));

        // expect error handling
        $this->errorManagerMock->expects($this->once())->method('handleError')->with(
            $this->stringContains('error to save metric: Database error'),
            $this->equalTo(Response::HTTP_INTERNAL_SERVER_ERROR)
        );

        // call tested method
        $this->metricsManager->saveMetric($metricName, $value);
    }
}
