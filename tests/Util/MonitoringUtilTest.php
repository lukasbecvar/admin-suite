<?php

namespace App\Tests\Util;

use Exception;
use App\Util\JsonUtil;
use App\Manager\LogManager;
use App\Util\MonitoringUtil;
use App\Manager\ErrorManager;
use PHPUnit\Framework\TestCase;
use App\Manager\ServiceManager;
use App\Manager\MetricsManager;
use App\Manager\MonitoringManager;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class MonitoringUtilTest
 *
 * Test cases for monitoring util
 *
 * @package App\Tests\Util
 */
#[CoversClass(MonitoringUtil::class)]
class MonitoringUtilTest extends TestCase
{
    private MonitoringUtil $monitoringUtil;
    private JsonUtil & MockObject $jsonUtilMock;
    private LogManager & MockObject $logManagerMock;
    private SymfonyStyle & MockObject $symfonyStyleMock;
    private ErrorManager & MockObject $errorManagerMock;
    private ServiceManager & MockObject $serviceManagerMock;
    private MetricsManager & MockObject $metricsManagerMock;
    private MonitoringManager & MockObject $monitoringManagerMock;

    protected function setUp(): void
    {
        // mock dependencies
        $this->jsonUtilMock = $this->createMock(JsonUtil::class);
        $this->logManagerMock = $this->createMock(LogManager::class);
        $this->symfonyStyleMock = $this->createMock(SymfonyStyle::class);
        $this->errorManagerMock = $this->createMock(ErrorManager::class);
        $this->serviceManagerMock = $this->createMock(ServiceManager::class);
        $this->metricsManagerMock = $this->createMock(MetricsManager::class);
        $this->monitoringManagerMock = $this->createMock(MonitoringManager::class);

        // create monitoring util instance
        $this->monitoringUtil = new MonitoringUtil(
            $this->jsonUtilMock,
            $this->logManagerMock,
            $this->errorManagerMock,
            $this->serviceManagerMock,
            $this->metricsManagerMock
        );
    }

    /**
     * Create a example systemd service configuration
     *
     * @return array<string, mixed> The service configuration
     */
    private function getSystemdServiceConfig(): array
    {
        return [
            'service_name' => 'nginx',
            'display_name' => 'Nginx',
            'monitoring' => true,
            'type' => 'systemd'
        ];
    }

    /**
     * Create a example http service configuration
     *
     * @param array<string, mixed> $overrides Overrides for the configuration
     *
     * @return array<string, mixed> The service configuration
     */
    private function getHttpServiceConfig(array $overrides = []): array
    {
        $base = [
            'service_name' => 'web',
            'display_name' => 'Web',
            'monitoring' => true,
            'type' => 'http',
            'url' => 'https://example.test',
            'accept_codes' => [200],
            'max_response_time' => 500,
            'metrics_monitoring' => [
                'collect_metrics' => 'true',
                'metrics_collector_url' => 'https://example.test/metrics'
            ]
        ];

        return array_replace_recursive($base, $overrides);
    }

    /**
     * Test monitoring systemd service when it is running
     *
     * @return void
     */
    public function testMonitorSystemdServiceWhenRunning(): void
    {
        $service = $this->getSystemdServiceConfig();

        $this->serviceManagerMock->expects($this->once())->method('isServiceRunning')->with('nginx')->willReturn(true);
        $this->monitoringManagerMock->expects($this->once())->method('handleMonitoringStatus')->with(
            'nginx',
            'running',
            $this->stringContains('Nginx is running')
        );
        $this->monitoringManagerMock->expects($this->never())->method('increaseDownTime');
        $this->symfonyStyleMock->expects($this->once())->method('writeln')->with(
            $this->stringContains('running without issues')
        );

        // call tested method
        $this->monitoringUtil->monitorSystemdService($this->symfonyStyleMock, $service, 10, $this->monitoringManagerMock);
    }

    /**
     * Test monitoring systemd service when it is down
     *
     * @return void
     */
    public function testMonitorSystemdServiceWhenNotRunning(): void
    {
        $service = $this->getSystemdServiceConfig();

        $this->serviceManagerMock->expects($this->once())->method('isServiceRunning')->willReturn(false);
        $this->monitoringManagerMock->expects($this->once())->method('handleMonitoringStatus')->with(
            'nginx',
            'not-running',
            $this->stringContains('not running')
        );
        $this->monitoringManagerMock->expects($this->once())->method('increaseDownTime')->with('nginx', 10);
        $this->symfonyStyleMock->expects($this->once())->method('writeln')->with($this->stringContains('is not running'));

        // call tested method
        $this->monitoringUtil->monitorSystemdService($this->symfonyStyleMock, $service, 10, $this->monitoringManagerMock);
    }

    /**
     * Test monitoring http service when it is offline
     *
     * @return void
     */
    public function testMonitorHttpServiceWhenOffline(): void
    {
        $service = $this->getHttpServiceConfig(['metrics_monitoring' => ['collect_metrics' => 'false']]);
        $this->serviceManagerMock->expects($this->once())->method('checkWebsiteStatus')->with('https://example.test')->willReturn([
            'isOnline' => false,
            'responseCode' => 0,
            'responseTime' => 0
        ]);
        $this->monitoringManagerMock->expects($this->once())->method('handleMonitoringStatus')
            ->with('web', 'not-online', $this->stringContains('offline'));
        $this->monitoringManagerMock->expects($this->once())->method('increaseDownTime')->with('web', 5);
        $this->symfonyStyleMock->expects($this->once())->method('writeln')->with($this->stringContains('is offline'));

        // call tested method
        $this->monitoringUtil->monitorHttpService($this->symfonyStyleMock, $service, 5, $this->monitoringManagerMock);
    }

    /**
     * Test monitoring http service when response code is rejected
     *
     * @return void
     */
    public function testMonitorHttpServiceWhenResponseCodeNotAccepted(): void
    {
        $service = $this->getHttpServiceConfig(['metrics_monitoring' => ['collect_metrics' => 'false']]);
        $this->serviceManagerMock->expects($this->once())->method('checkWebsiteStatus')->willReturn([
            'isOnline' => true,
            'responseCode' => 500,
            'responseTime' => 100
        ]);
        $this->monitoringManagerMock->expects($this->once())->method('handleMonitoringStatus')
            ->with('web', 'not-accepting-code', $this->stringContains('not accepting any of the codes'));
        $this->monitoringManagerMock->expects($this->once())->method('increaseDownTime')->with('web', 7);
        $this->symfonyStyleMock->expects($this->once())->method('writeln')
            ->with($this->stringContains('not accepting any of the codes'));

        // call tested method
        $this->monitoringUtil->monitorHttpService($this->symfonyStyleMock, $service, 7, $this->monitoringManagerMock);
    }

    /**
     * Test monitoring http service when response time exceeds threshold
     *
     * @return void
     */
    public function testMonitorHttpServiceWhenResponseTimeTooHigh(): void
    {
        $service = $this->getHttpServiceConfig(['metrics_monitoring' => ['collect_metrics' => 'false']]);
        $this->serviceManagerMock->expects($this->once())->method('checkWebsiteStatus')->willReturn([
            'isOnline' => true,
            'responseCode' => 200,
            'responseTime' => 900
        ]);
        $this->monitoringManagerMock->expects($this->once())->method('handleMonitoringStatus')
            ->with('web', 'not-responding', $this->stringContains('not responding in'));
        $this->monitoringManagerMock->expects($this->once())->method('increaseDownTime')->with('web', 3);
        $this->symfonyStyleMock->expects($this->once())->method('writeln')->with($this->stringContains('not responding'));

        // call tested method
        $this->monitoringUtil->monitorHttpService($this->symfonyStyleMock, $service, 3, $this->monitoringManagerMock);
    }

    /**
     * Test monitoring http service when metrics are collected successfully
     *
     * @return void
     */
    public function testMonitorHttpServiceCollectsMetricsWhenOnline(): void
    {
        $service = $this->getHttpServiceConfig();
        $this->serviceManagerMock->expects($this->once())->method('checkWebsiteStatus')->willReturn([
            'isOnline' => true,
            'responseCode' => 200,
            'responseTime' => 120
        ]);
        $this->jsonUtilMock->expects($this->once())->method('getJson')
            ->with('https://example.test/metrics', 30)->willReturn(['latency' => 10]);
        $this->metricsManagerMock->expects($this->once())->method('saveServiceMetric')
            ->with('latency', 10, 'web')->willReturn(true);
        $this->monitoringManagerMock->expects($this->once())->method('handleMonitoringStatus')
            ->with('web', 'online', $this->stringContains('is online'));
        $this->monitoringManagerMock->expects($this->never())->method('increaseDownTime');
        $this->symfonyStyleMock->expects($this->atLeast(2))->method('writeln');

        // call tested method
        $this->monitoringUtil->monitorHttpService($this->symfonyStyleMock, $service, 4, $this->monitoringManagerMock);
    }

    /**
     * Test monitoring http service logs error when metric saving fails
     *
     * @return void
     */
    public function testMonitorHttpServiceLogsErrorWhenMetricSaveFails(): void
    {
        $service = $this->getHttpServiceConfig();
        $this->serviceManagerMock->expects($this->once())->method('checkWebsiteStatus')->willReturn([
            'isOnline' => true,
            'responseCode' => 200,
            'responseTime' => 110
        ]);
        $this->jsonUtilMock->expects($this->once())->method('getJson')->willReturn(['latency' => 5]);
        $this->metricsManagerMock->expects($this->once())->method('saveServiceMetric')
            ->willThrowException(new Exception('db failure'));
        $this->errorManagerMock->expects($this->once())->method('logError')->with(
            'Error to save metric: db failure',
            Response::HTTP_INTERNAL_SERVER_ERROR
        );
        $this->monitoringManagerMock->expects($this->once())->method('handleMonitoringStatus')
            ->with('web', 'online', $this->stringContains('is online'));
        $this->monitoringManagerMock->expects($this->never())->method('increaseDownTime');
        $this->symfonyStyleMock->expects($this->once())->method('writeln')
            ->with($this->stringContains('is online'));

        // call tested method
        $this->monitoringUtil->monitorHttpService($this->symfonyStyleMock, $service, 4, $this->monitoringManagerMock);
    }

    /**
     * Test monitoring http service when metrics endpoint cannot be reached
     *
     * @return void
     */
    public function testMonitorHttpServiceLogsWarningWhenMetricsCannotBeFetched(): void
    {
        $service = $this->getHttpServiceConfig();
        $this->serviceManagerMock->expects($this->once())->method('checkWebsiteStatus')->willReturn([
            'isOnline' => true,
            'responseCode' => 200,
            'responseTime' => 150
        ]);
        $this->jsonUtilMock->expects($this->once())->method('getJson')->willReturn(null);
        $this->logManagerMock->expects($this->once())->method('log')
            ->with('monitoring', $this->stringContains('error to get metrics'), LogManager::LEVEL_WARNING);
        $this->symfonyStyleMock->expects($this->once())->method('error')
            ->with($this->stringContains('error to get metrics'));
        $this->monitoringManagerMock->expects($this->once())->method('handleMonitoringStatus')
            ->with('web', 'online', $this->stringContains('is online'));

        // call tested method
        $this->monitoringUtil->monitorHttpService($this->symfonyStyleMock, $service, 6, $this->monitoringManagerMock);
    }
}
