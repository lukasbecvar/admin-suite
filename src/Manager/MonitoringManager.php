<?php

namespace App\Manager;

use DateTime;
use Exception;
use App\Util\AppUtil;
use App\Util\JsonUtil;
use App\Util\CacheUtil;
use App\Util\ServerUtil;
use App\Entity\SLAHistory;
use App\Entity\MonitoringStatus;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\SLAHistoryRepository;
use Symfony\Component\HttpFoundation\Response;
use App\Repository\MonitoringStatusRepository;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class MonitoringManager
 *
 * Manager for monitoring component
 *
 * @package App\Manager
 */
class MonitoringManager
{
    private AppUtil $appUtil;
    private JsonUtil $jsonUtil;
    private CacheUtil $cacheUtil;
    private LogManager $logManager;
    private ServerUtil $serverUtil;
    private EmailManager $emailManager;
    private ErrorManager $errorManager;
    private MetricsManager $metricsManager;
    private ServiceManager $serviceManager;
    private SLAHistoryRepository $slaHistoryRepository;
    private NotificationsManager $notificationsManager;
    private EntityManagerInterface $entityManagerInterface;
    private MonitoringStatusRepository $monitoringStatusRepository;

    public function __construct(
        AppUtil $appUtil,
        JsonUtil $jsonUtil,
        CacheUtil $cacheUtil,
        LogManager $logManager,
        ServerUtil $serverUtil,
        EmailManager $emailManager,
        ErrorManager $errorManager,
        MetricsManager $metricsManager,
        ServiceManager $serviceManager,
        SLAHistoryRepository $slaHistoryRepository,
        NotificationsManager $notificationsManager,
        EntityManagerInterface $entityManagerInterface,
        MonitoringStatusRepository $monitoringStatusRepository
    ) {
        $this->appUtil = $appUtil;
        $this->jsonUtil = $jsonUtil;
        $this->cacheUtil = $cacheUtil;
        $this->logManager = $logManager;
        $this->serverUtil = $serverUtil;
        $this->emailManager = $emailManager;
        $this->errorManager = $errorManager;
        $this->metricsManager = $metricsManager;
        $this->serviceManager = $serviceManager;
        $this->slaHistoryRepository = $slaHistoryRepository;
        $this->notificationsManager = $notificationsManager;
        $this->entityManagerInterface = $entityManagerInterface;
        $this->monitoringStatusRepository = $monitoringStatusRepository;
    }

    /**
     * Get service monitoring repository
     *
     * @param array<mixed> $search The search parameters
     *
     * @return MonitoringStatus|null The service monitoring repository
     */
    public function getMonitoringStatusRepository(array $search): ?MonitoringStatus
    {
        try {
            return $this->monitoringStatusRepository->findOneBy($search);
        } catch (Exception $e) {
            $this->errorManager->handleError(
                message: 'error to get service monitoring repository: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Set monitoring status for service
     *
     * @param string $serviceName The name of the service
     * @param string $message The message to set for the service
     * @param string $status The status to set for the service
     *
     * @return void
     */
    public function setMonitoringStatus(string $serviceName, string $message, string $status): void
    {
        // get service monitoring repository
        $monitoringStatus = $this->monitoringStatusRepository->findOneBy(['service_name' => $serviceName]);

        // create monitoring status (if not found)
        if ($monitoringStatus == null) {
            // set monitored service properties
            $monitoringStatus = new MonitoringStatus();
            $monitoringStatus->setServiceName($serviceName)
                ->setStatus($status)
                ->setMessage('new service initialization')
                ->setDownTime(0)
                ->setSlaTimeframe(date('Y-m'))
                ->setLastUpdateTime(new DateTime());

            // persist monitoring status
            $this->entityManagerInterface->persist($monitoringStatus);

        // update monitoring status properties (if found)
        } else {
            $monitoringStatus->setMessage($message)
                ->setStatus($status)
                ->setLastUpdateTime(new DateTime());
        }

        try {
            // flush changes to database
            $this->entityManagerInterface->flush();
        } catch (Exception $e) {
            $this->errorManager->handleError(
                message: 'error to flush service monitoring: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Get monitoring status for service
     *
     * @param string $serviceName The name of the service
     *
     * @return string|null The service monitoring status
     */
    public function getMonitoringStatus(string $serviceName): ?string
    {
        try {
            /** @var \App\Entity\MonitoringStatus $repo monitored service repository */
            $repo = $this->monitoringStatusRepository->findOneBy(['service_name' => $serviceName]);

            // check if repository is found
            if ($repo != null) {
                return $repo->getStatus();
            }

            return null;
        } catch (Exception $e) {
            $this->errorManager->handleError(
                message: 'error to get service status: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Handle monitoring status for a service
     *
     * @param string $serviceName The name of the service
     * @param string $currentStatus The current status of the service
     * @param string $message The message to set for the service
     *
     * @return void
     */
    public function handleMonitoringStatus(string $serviceName, string $currentStatus, string $message): void
    {
        // get monitoring interval
        $monitoringInterval = (int) $this->appUtil->getEnvValue('MONITORING_INTERVAL') * 60;

        // check if monitoring status is not changed (if is status same multiple times)
        if ($this->cacheUtil->getValue('monitoring-status-' . $serviceName)->get() != $currentStatus) {
            // store current status in cache
            $this->cacheUtil->setValue(
                'monitoring-status-' . $serviceName,
                $currentStatus,
                ($monitoringInterval + ($monitoringInterval / 2))
            );
            return;
        }

        // get monitoring status
        $lastStatus = $this->getMonitoringStatus($serviceName);

        // check if status changed
        if ($lastStatus != $currentStatus) {
            // send monitoring status email
            $this->emailManager->sendMonitoringStatusEmail(
                $this->appUtil->getEnvValue('ADMIN_CONTACT'),
                $serviceName,
                $message
            );

            // send monitoring status notification
            $this->notificationsManager->sendNotification('monitoring ' . $serviceName, '[' . date('H:i') . ']: ' . $message);

            // log status chnage event
            $this->logManager->log(
                name: 'monitoring',
                message: $serviceName . ' status: ' . $currentStatus . ' msg: ' . $message,
                level: LogManager::LEVEL_WARNING
            );

            // update monitoring status
            $this->setMonitoringStatus($serviceName, $message, $currentStatus);
        }
    }

    /**
     * Increase down time for specific service
     *
     * @param string $serviceName The name of the service
     * @param int $minutes The number of minutes to increase the down time
     *
     * @return void
     */
    public function increaseDownTime(string $serviceName, int $minutes): void
    {
        // get monitoring status repository
        $repo = $this->monitoringStatusRepository->findOneBy(['service_name' => $serviceName]);

        // check if repository is found
        if ($repo == null) {
            $this->setMonitoringStatus($serviceName, 'new service initialization', 'pending');
            return;
        }

        // increase down time
        try {
            $repo->increaseDownTime($minutes);
            $this->entityManagerInterface->flush();
        } catch (Exception $e) {
            $this->errorManager->handleError(
                message: 'error to increase down time: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Get service SLA (for current month timeframe)
     *
     * @param string $serviceName The name of the service
     *
     * @return int|float|null The service SLA, null if not found
     */
    public function getServiceMountlySLA(string $serviceName): int|float|null
    {
        /** @var MonitoringStatus $repo */
        $repo = $this->monitoringStatusRepository->findOneBy(['service_name' => $serviceName]);

        // check if repository is found
        if ($repo == null) {
            return null;
        }

        // get down time (minutes)
        $downTime = $repo->getDownTime();
        if ($downTime === null) {
            $this->errorManager->handleError(
                message: 'error to get service SLA: down time is null',
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        // total minutes in a month (average month length in days)
        $totalMinutesInMonth = 30.44 * 24 * 60;

        // calculate SLA
        return round((1 - ($downTime / $totalMinutesInMonth)) * 100, 2);
    }

    /**
     * Get SLA history
     *
     * @return array<array<string, float>> The SLA history data
     */
    public function getSLAHistory(): array
    {
        $data = [];
        try {
            // get data from database
            $slaHistoryData = $this->slaHistoryRepository->findAll();

            // convert data to array
            foreach ($slaHistoryData as $slaHistory) {
                $serviceName = $slaHistory->getServiceName();
                $timeframe = $slaHistory->getSlaTimeframe() ?? 'N/A';
                $slaValue = $slaHistory->getSlaValue() ?? 0.0;

                // format timeframe (month - year)
                if ($timeframe != 'N/A') {
                    $date = DateTime::createFromFormat('Y-m', $timeframe);
                    $formattedTimeframe = $date ? $date->format('F - Y') : $timeframe;
                } else {
                    $formattedTimeframe = $timeframe;
                }

                // initialize array for service if not already set
                if (!isset($data[$serviceName])) {
                    $data[$serviceName] = [];
                }

                // add formatted timeframe and SLA value to the service array
                $data[$serviceName][$formattedTimeframe] = $slaValue;
            }
        } catch (Exception $e) {
            $this->errorManager->handleError(
                message: 'error to get SLA history: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        return $data;
    }

    /**
     * Save service SLA to history
     *
     * @param string $serviceName The name of the service
     * @param string $slaTimeframe The timeframe of the SLA
     * @param float $slaValue The SLA value
     *
     * @return void
     */
    public function saveSLAHistory(string $serviceName, string $slaTimeframe, float $slaValue): void
    {
        $slaHistory = new SLAHistory();
        $slaHistory->setServiceName($serviceName)
            ->setSlaTimeframe($slaTimeframe)
            ->setSlaValue($slaValue);

        try {
            $this->entityManagerInterface->persist($slaHistory);
            $this->entityManagerInterface->flush();
        } catch (Exception $e) {
            $this->errorManager->handleError(
                message: 'error to save SLA history: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Reset down times for all services
     *
     * @param SymfonyStyle $io The Symfony command output decorator
     *
     * @return void
     */
    public function resetDownTimes(SymfonyStyle $io): void
    {
        // excluded services from reset
        $excludedServices = [
            'system-cpu-usage',
            'system-ram-usage',
            'system-storage-usage'
        ];

        // get current timeframe
        $currentTimeframe = date('Y-m');

        /** @var array<MonitoringStatus> $monitoringStatusRepository */
        $monitoringStatusRepository = $this->monitoringStatusRepository->findByNonCurrentTimeframe($currentTimeframe);

        // check if any services is in non current timeframe
        if ($monitoringStatusRepository == null) {
            return;
        }

        // reset down times for all services
        foreach ($monitoringStatusRepository as $monitoringStatus) {
            $this->entityManagerInterface->refresh($monitoringStatus);

            // check if service is excluded
            if (in_array($monitoringStatus->getServiceName(), $excludedServices)) {
                continue;
            }

            // check if service is in current timeframe
            if ($monitoringStatus->getSlaTimeframe() != $currentTimeframe) {
                // get old timeframe
                $serviceName = $monitoringStatus->getServiceName();
                $oldTimeframe = $monitoringStatus->getSlaTimeframe();
                if ($oldTimeframe == null) {
                    $this->errorManager->handleError(
                        message: 'error to reset SLA: old timeframe is null',
                        code: Response::HTTP_INTERNAL_SERVER_ERROR
                    );
                }

                // check if service name set
                if ($serviceName == null) {
                    $this->errorManager->handleError(
                        message: 'error to reset SLA: service name is null',
                        code: Response::HTTP_INTERNAL_SERVER_ERROR
                    );
                }

                // get sla before reset
                $slaBeforeReset = $this->getServiceMountlySLA($serviceName);
                if ($slaBeforeReset == null) {
                    $this->errorManager->handleError(
                        message: 'error to calculate SLA before reset: sla value is null',
                        code: Response::HTTP_INTERNAL_SERVER_ERROR
                    );
                }

                // log SLA status to database
                $this->logManager->log(
                    name: 'SLA timeframe reset',
                    message: $monitoringStatus->getServiceName() . ' SLA for timeframe ' . $oldTimeframe . ' is: ' . $slaBeforeReset . '%',
                    level: LogManager::LEVEL_INFO
                );

                // save SLA history
                $this->saveSLAHistory($serviceName, $oldTimeframe, $slaBeforeReset);

                // log to command output
                $io->writeln(
                    '[' . date('Y-m-d H:i:s') . '] monitoring: <fg=yellow>SLA downtime for service ' . $monitoringStatus->getServiceName() . ' reseted</>'
                );

                // reset SLA timeframe
                $monitoringStatus->setSlaTimeframe($currentTimeframe);
                $monitoringStatus->setDownTime(0);
            }
        }

        // flush changes to database
        try {
            $this->entityManagerInterface->flush();
        } catch (Exception $e) {
            $this->errorManager->handleError(
                message: 'error to reset down times: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Handle database down
     *
     * @param SymfonyStyle $io The command output decorator
     * @param bool $databaseDown The database down flag
     *
     * @return void
     */
    public function handleDatabaseDown(SymfonyStyle $io, bool $databaseDown): void
    {
        // check if database is down flag is set (if database is not down before)
        if ($databaseDown == false) {
            $this->emailManager->sendMonitoringStatusEmail(
                recipient: $this->appUtil->getEnvValue('ADMIN_CONTACT'),
                serviceName: 'Mysql',
                message: 'Mysql server is down'
            );
        }

        // print database is down message
        $io->writeln('[' . date('Y-m-d H:i:s') . '] monitoring: <fg=red>database is down</fg=red>');
    }

    /**
     * Temporarily disable monitoring for specific service
     *
     * @param string $serviceName The name of the service
     * @param int $minutes The number of minutes to disable monitoring
     *
     * @return void
     */
    public function temporaryDisableMonitoring(string $serviceName, int $minutes): void
    {
        // check if service exists in monitoring config
        $servicesList = $this->serviceManager->getServicesList() ?? [];
        if (!array_key_exists($serviceName, $servicesList)) {
            $this->errorManager->handleError(
                message: 'error to disable monitoring: service ' . $serviceName . ' not found',
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        // convert seconds to minutes
        $minutes = $minutes * 60;

        // set monitoring status to pending
        $this->cacheUtil->setValue('monitoring-temporary-disabled-' . $serviceName, 'disabled', $minutes);
    }

    /**
     * Check if monitoring is temporarily disabled for specific service
     *
     * @param string $serviceName The name of the service
     *
     * @return bool|null The monitoring status
     */
    public function isMonitoringTemporarilyDisabled(string $serviceName): ?bool
    {
        if ($this->cacheUtil->isCatched('monitoring-temporary-disabled-' . $serviceName)) {
            return true;
        }

        return false;
    }

    /**
     * Init monitoring process (called from monitoring process command)
     *
     * @param SymfonyStyle $io The command output decorator
     *
     * @return void
     */
    public function monitorInit(SymfonyStyle $io): void
    {
        // check if method is called from cli
        if (php_sapi_name() != 'cli') {
            $this->errorManager->handleError(
                message: 'error to init monitoring process: this method can be called only from cli',
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        // reset down times for all services (reset data for SLA calculation)
        $this->resetDownTimes($io);

        // get monitoring interval
        $monitoringInterval = (int) $this->appUtil->getEnvValue('MONITORING_INTERVAL') * 60;
        $possibleDownTime = $monitoringInterval / 60;

        // get current resource usages
        $cpuUsage = $this->serverUtil->getCpuUsage();
        $ramUsage = $this->serverUtil->getRamUsagePercentage();
        $storageUsage = (int) $this->serverUtil->getDriveUsagePercentage();

        // get network usage
        $networkStats = $this->serverUtil->getNetworkStats();

        // monitor cpu usage
        if ($cpuUsage > 98) {
            $this->handleMonitoringStatus(
                serviceName: 'system-cpu-usage',
                currentStatus: 'critical',
                message: 'cpu usage is too high'
            );
            $io->writeln(
                '[' . date('Y-m-d H:i:s') . '] monitoring: <fg=red>cpu usage is too high (current: ' . $cpuUsage . '%)</fg=red>'
            );
        } else {
            $this->handleMonitoringStatus(
                serviceName: 'system-cpu-usage',
                currentStatus: 'normal',
                message: 'cpu usage is normal'
            );
            $io->writeln(
                '[' . date('Y-m-d H:i:s') . '] monitoring: <fg=green>cpu usage is in normal range (current: ' . $cpuUsage . '%)</fg=green>'
            );
        }

        // monitor ram usage
        if ($ramUsage > 98) {
            $this->handleMonitoringStatus(
                serviceName: 'system-ram-usage',
                currentStatus: 'critical',
                message: 'ram usage is too high'
            );
            $io->writeln(
                '[' . date('Y-m-d H:i:s') . '] monitoring: <fg=red>ram usage is too high (current: ' . $ramUsage . '%)</fg=red>'
            );
        } else {
            $this->handleMonitoringStatus(
                serviceName: 'system-ram-usage',
                currentStatus: 'normal',
                message: 'ram usage is normal'
            );
            $io->writeln(
                '[' . date('Y-m-d H:i:s') . '] monitoring: <fg=green>ram usage is in normal range (current: ' . $ramUsage . '%)</fg=green>'
            );
        }

        // monitor storage usage
        if ($storageUsage > 98) {
            $this->handleMonitoringStatus(
                serviceName: 'system-storage-usage',
                currentStatus: 'critical',
                message: 'storage space on the disk is not enough'
            );
            $io->writeln(
                '[' . date('Y-m-d H:i:s') . '] monitoring: <fg=red>storage space on the disk is not enough (current: ' . $storageUsage . '%)</fg=red>'
            );
        } else {
            $this->handleMonitoringStatus(
                serviceName: 'system-storage-usage',
                currentStatus: 'normal',
                message: 'storage space is normal'
            );
            $io->writeln(
                '[' . date('Y-m-d H:i:s') . '] monitoring: <fg=green>storage space is in normal range (current: ' . $storageUsage . '%)</fg=green>'
            );
        }

        /** @var array<array<mixed>> $services */
        $services = $this->serviceManager->getServicesList();

        // check if services list is iterable
        if (!is_iterable($services)) {
            $io->error('Error to iterate services list');
            return;
        }

        // handle configured services status
        foreach ($services as $service) {
            // check if service is enabled
            if ($service['monitoring'] == false) {
                continue;
            }

            // check if monitoring is temporary disabled
            if ($this->isMonitoringTemporarilyDisabled($service['service_name'])) {
                $io->writeln('<fg=yellow>monitoring for service ' . $service['service_name'] . ' skipped (check temporarily disabled)</>');
                continue;
            }

            // monitor systemd services
            if ($service['type'] == 'systemd') {
                // check if service is running
                if ($this->serviceManager->isServiceRunning($service['service_name'])) {
                    $this->handleMonitoringStatus(
                        serviceName: $service['service_name'],
                        currentStatus: 'running',
                        message:$service['display_name'] . ' is running'
                    );
                    $io->writeln(
                        '[' . date('Y-m-d H:i:s') . '] monitoring: <fg=green>' . $service['display_name'] . ' is running without issues</fg=green>'
                    );
                } else {
                    $this->handleMonitoringStatus(
                        serviceName: $service['service_name'],
                        currentStatus: 'not-running',
                        message: $service['display_name'] . ' is not running'
                    );
                    $this->increaseDownTime($service['service_name'], $possibleDownTime);
                    $io->writeln(
                        '[' . date('Y-m-d H:i:s') . '] monitoring: <fg=red>' . $service['display_name'] . ' is not running</fg=red>'
                    );
                }
            }

            // monitor http services
            if ($service['type'] == 'http') {
                // get service status
                $serviceStatus = $this->serviceManager->checkWebsiteStatus($service['url']);

                // check if service is online
                if ($serviceStatus['isOnline']) {
                    // check service response code
                    if (!in_array($serviceStatus['responseCode'], $service['accept_codes'])) {
                        // split accept codes to string
                        $acceptCodesStr = implode(', ', $service['accept_codes']);

                        // handle http service status
                        $this->handleMonitoringStatus(
                            serviceName: $service['service_name'],
                            currentStatus: 'not-accepting-code',
                            message: $service['display_name'] . ' is not accepting any of the codes ' . $acceptCodesStr . ' (response code: ' . $serviceStatus['responseCode'] . ')'
                        );
                        $this->increaseDownTime($service['service_name'], $possibleDownTime);
                        $io->writeln(
                            '[' . date('Y-m-d H:i:s') . '] monitoring: <fg=red>' . $service['display_name'] . ' is not accepting any of the codes ' . $acceptCodesStr . ' (response code: ' . $serviceStatus['responseCode'] . ')</fg=red>'
                        );
                    // check service response time
                    } elseif ($serviceStatus['responseTime'] > $service['max_response_time']) {
                        // handle http service status
                        $this->handleMonitoringStatus(
                            serviceName: $service['service_name'],
                            currentStatus: 'not-responding',
                            message: $service['display_name'] . ' is not responding in ' . $service['max_response_time'] . 'ms'
                        );
                        $this->increaseDownTime($service['service_name'], $possibleDownTime);
                        $io->writeln(
                            '[' . date('Y-m-d H:i:s') . '] monitoring: <fg=red>' . $service['display_name'] . ' is not responding in ' . $service['max_response_time'] . ' ms</fg=red>'
                        );

                    // status ok
                    } else {
                        $this->handleMonitoringStatus(
                            serviceName: $service['service_name'],
                            currentStatus: 'online',
                            message: $service['display_name'] . ' is online'
                        );
                        $io->writeln(
                            '[' . date('Y-m-d H:i:s') . '] monitoring: <fg=green>' . $service['display_name'] . ' is online (response code: ' . $serviceStatus['responseCode'] . ', response time: ' . $serviceStatus['responseTime'] . 'ms)</>'
                        );

                        // check if metrics can be collected
                        if ($service['metrics_monitoring']['collect_metrics'] == 'true') {
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
                    $this->handleMonitoringStatus(
                        serviceName: $service['service_name'],
                        currentStatus: 'not-online',
                        message: $service['display_name'] . ' is offline'
                    );
                    $this->increaseDownTime($service['service_name'], $possibleDownTime);
                    $io->writeln(
                        '[' . date('Y-m-d H:i:s') . '] monitoring: <fg=red>' . $service['display_name'] . ' is offline</fg=red>'
                    );
                }
            }
        }

        // save host usages metrics to database
        try {
            $this->metricsManager->saveUsageMetrics($cpuUsage, $ramUsage, $storageUsage, (float) $networkStats['networkUsagePercent']);
            $io->writeln(
                '[' . date('Y-m-d H:i:s') . '] monitoring: <fg=green>host usages metrics saved</fg=green>'
            );
        } catch (Exception $e) {
            $this->errorManager->logError(
                message: $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        // calculate last monitoring time expiration
        $lastMonitoringTimeExpiration = (intval($monitoringInterval) * 60) * 2;

        // save last monitoring time to cache
        $this->cacheUtil->setValue('last-monitoring-time', new DateTime(), $lastMonitoringTimeExpiration);
    }
}
