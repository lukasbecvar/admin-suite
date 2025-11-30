<?php

namespace App\Util;

use Exception;
use App\Manager\LogManager;
use App\Manager\ErrorManager;
use App\Manager\MetricsManager;
use App\Manager\ServiceManager;
use App\Manager\MonitoringManager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class MonitoringUtil
 *
 * Utility for monitoring different service types
 *
 * @package App\Util
 */
class MonitoringUtil
{
    private JsonUtil $jsonUtil;
    private LogManager $logManager;
    private ErrorManager $errorManager;
    private ServiceManager $serviceManager;
    private MetricsManager $metricsManager;

    public function __construct(
        JsonUtil $jsonUtil,
        LogManager $logManager,
        ErrorManager $errorManager,
        ServiceManager $serviceManager,
        MetricsManager $metricsManager,
    ) {
        $this->jsonUtil = $jsonUtil;
        $this->logManager = $logManager;
        $this->errorManager = $errorManager;
        $this->serviceManager = $serviceManager;
        $this->metricsManager = $metricsManager;
    }

    /**
     * Monitor systemd service
     *
     * @param SymfonyStyle $io The Symfony command output decorator
     * @param array<mixed> $service The service configuration array
     * @param int $possibleDownTime The possible down time in minutes
     * @param MonitoringManager $monitoringManager The MonitoringManager instance to call back to for status updates
     *
     * @return void
     */
    public function monitorSystemdService(SymfonyStyle $io, array $service, int $possibleDownTime, MonitoringManager $monitoringManager): void
    {
        // check if service is running
        if ($this->serviceManager->isServiceRunning($service['service_name'])) {
            $monitoringManager->handleMonitoringStatus(
                serviceName: $service['service_name'],
                currentStatus: 'running',
                message:$service['display_name'] . ' is running'
            );
            $io->writeln(
                '[' . date('Y-m-d H:i:s') . '] monitoring: <fg=green>' . $service['display_name'] . ' is running without issues</fg=green>'
            );
        } else {
            $monitoringManager->handleMonitoringStatus(
                serviceName: $service['service_name'],
                currentStatus: 'not-running',
                message: $service['display_name'] . ' is not running'
            );
            $monitoringManager->increaseDownTime($service['service_name'], $possibleDownTime);
            $io->writeln(
                '[' . date('Y-m-d H:i:s') . '] monitoring: <fg=red>' . $service['display_name'] . ' is not running</fg=red>'
            );
        }
    }

    /**
     * Monitor http service
     *
     * @param SymfonyStyle $io The Symfony command output decorator
     * @param array<mixed> $service The service configuration array
     * @param int $possibleDownTime The possible down time in minutes
     * @param MonitoringManager $monitoringManager The MonitoringManager instance to call back to for status updates
     *
     * @return void
     */
    public function monitorHttpService(SymfonyStyle $io, array $service, int $possibleDownTime, MonitoringManager $monitoringManager): void
    {
        // get service status
        $serviceStatus = $this->serviceManager->checkWebsiteStatus($service['url']);
        $canCollectMetrics = $service['metrics_monitoring']['collect_metrics'] == 'true' && $serviceStatus['isOnline'];

        // check if service is online
        if ($serviceStatus['isOnline']) {
            // check service response code
            if (!in_array($serviceStatus['responseCode'], $service['accept_codes'])) {
                // split accept codes to string
                $acceptCodesStr = implode(', ', $service['accept_codes']);

                // handle http service status
                $monitoringManager->handleMonitoringStatus(
                    serviceName: $service['service_name'],
                    currentStatus: 'not-accepting-code',
                    message: $service['display_name'] . ' is not accepting any of the codes ' . $acceptCodesStr . ' (response code: ' . $serviceStatus['responseCode'] . ')'
                );
                $monitoringManager->increaseDownTime($service['service_name'], $possibleDownTime);
                $io->writeln(
                    '[' . date('Y-m-d H:i:s') . '] monitoring: <fg=red>' . $service['display_name'] . ' is not accepting any of the codes ' . $acceptCodesStr . ' (response code: ' . $serviceStatus['responseCode'] . ')</fg=red>'
                );
                // check service response time
            } elseif ($serviceStatus['responseTime'] > $service['max_response_time']) {
                // handle http service status
                $monitoringManager->handleMonitoringStatus(
                    serviceName: $service['service_name'],
                    currentStatus: 'not-responding',
                    message: $service['display_name'] . ' is not responding in ' . $service['max_response_time'] . 'ms'
                );
                $monitoringManager->increaseDownTime($service['service_name'], $possibleDownTime);
                $io->writeln(
                    '[' . date('Y-m-d H:i:s') . '] monitoring: <fg=red>' . $service['display_name'] . ' is not responding in ' . $service['max_response_time'] . ' ms</fg=red>'
                );

                // status ok
            } else {
                $monitoringManager->handleMonitoringStatus(
                    serviceName: $service['service_name'],
                    currentStatus: 'online',
                    message: $service['display_name'] . ' is online'
                );
                $io->writeln(
                    '[' . date('Y-m-d H:i:s') . '] monitoring: <fg=green>' . $service['display_name'] . ' is online (response code: ' . $serviceStatus['responseCode'] . ', response time: ' . $serviceStatus['responseTime'] . 'ms)</>'
                );

                // check if metrics can be collected
                if ($canCollectMetrics) {
                    // get metrics from metrics collector
                    $metrics = $this->jsonUtil->getJson($service['metrics_monitoring']['metrics_collector_url'], 30);

                    // check if metrics get is successful
                    if ($metrics == null) {
                        // handle error
                        $errorMessage = 'error to get metrics from ' . $service['metrics_monitoring']['metrics_collector_url'];
                        $io->error($errorMessage);
                        $this->logManager->log(
                            name: 'monitoring',
                            message: $errorMessage,
                            level: LogManager::LEVEL_WARNING
                        );
                    } else {
                        // collect service metrics
                        foreach ($metrics as $name => $value) {
                            try {
                                $metricSaveStatus = $this->metricsManager->saveServiceMetric(
                                    metricName: $name,
                                    value: $value,
                                    serviceName: $service['service_name']
                                );
                                if ($metricSaveStatus) {
                                    $io->writeln(
                                        '[' . date('Y-m-d H:i:s') . '] monitoring: <fg=green>metric ' . $name . ' from service ' . $service['display_name'] . ' saved</fg=green>'
                                    );
                                }
                            } catch (Exception $e) {
                                $this->errorManager->logError(
                                    message: 'Error to save metric: ' . $e->getMessage(),
                                    code: Response::HTTP_INTERNAL_SERVER_ERROR
                                );
                            }
                        }
                    }
                }
            }

        // handle service offline status
        } else {
            $monitoringManager->handleMonitoringStatus(
                serviceName: $service['service_name'],
                currentStatus: 'not-online',
                message: $service['display_name'] . ' is offline'
            );
            $monitoringManager->increaseDownTime($service['service_name'], $possibleDownTime);
            $io->writeln(
                '[' . date('Y-m-d H:i:s') . '] monitoring: <fg=red>' . $service['display_name'] . ' is offline</fg=red>'
            );
        }
    }
}
