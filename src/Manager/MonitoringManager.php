<?php

namespace App\Manager;

use DateTime;
use Exception;
use App\Util\AppUtil;
use App\Util\JsonUtil;
use App\Util\CacheUtil;
use App\Util\ServerUtil;
use App\Entity\MonitoringStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use App\Repository\MonitoringStatusRepository;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class MonitoringManager
 *
 * The manager for monitoring component functionality
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
        $this->notificationsManager = $notificationsManager;
        $this->entityManagerInterface = $entityManagerInterface;
        $this->monitoringStatusRepository = $monitoringStatusRepository;
    }

    /**
     * Get service monitoring repository
     *
     * @param array<mixed> $search The search parameters
     *
     * @throws Exception Error while retrieving the repository
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
     * Set monitoring status for a service
     *
     * @param string $serviceName The name of the service
     * @param string $message The message to set for the service
     * @param string $status The status to set for the service
     *
     * @throws Exception Error to flush monitoring status
     *
     * @return void
     */
    public function setMonitoringStatus(string $serviceName, string $message, string $status): void
    {
        // get service monitoring repository
        $monitoringStatus = $this->monitoringStatusRepository->findOneBy(['service_name' => $serviceName]);

        // create monitoring status (if not found)
        if ($monitoringStatus == null) {
            $monitoringStatus = new MonitoringStatus();

            // set monitored service properties
            $monitoringStatus->setServiceName($serviceName)
                ->setStatus($status)
                ->setMessage('new service initialization')
                ->setLastUpdateTime(new DateTime());

            // persist service monitoring
            $this->entityManagerInterface->persist($monitoringStatus);

        // update service monitoring properties (if found)
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
     * Get monitoring status for a service
     *
     * @param string $serviceName The name of the service
     *
     * @throws Exception Error to get monitoring status
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
     * Handle database down
     *
     * @param SymfonyStyle $io The io interface
     * @param bool $databaseDown The database down flag
     *
     * @return void
     */
    public function handleDatabaseDown(SymfonyStyle $io, bool $databaseDown): void
    {
        // check if database is down flag is set
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
     * Disable next monitoring for a service
     *
     * This method is used to disable next monitoring for a service after amount of time
     *
     * @param string $serviceName The name of the service
     * @param int $minutes The number of minutes to disable next monitoring
     *
     * @return void
     */
    public function disableNextMonitoring(string $serviceName, int $minutes): void
    {
        $this->cacheUtil->setValue('monitoring-disabler-' . $serviceName, false, $minutes * 60);
    }

    /**
     * Init monitoring process (called from monitoring process command)
     *
     * @param SymfonyStyle $io The io interface
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

        // check if monitoring is disabled
        if ($this->cacheUtil->isCatched('monitoring-disabler-complete-monitoring-job')) {
            $io->writeln(
                '[' . date('Y-m-d H:i:s') . '] monitoring: <fg=yellow>monitoring skipped for complete monitoring job</>'
            );
            return;
        }

        // get monitoring interval
        $monitoringInterval = (int) $this->appUtil->getEnvValue('MONITORING_INTERVAL') * 60;

        // get metrics
        $cpuUsage = $this->serverUtil->getCpuUsage();
        $ramUsage = $this->serverUtil->getRamUsagePercentage();
        $storageUsage = (int) $this->serverUtil->getDriveUsagePercentage();

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

        // get monitored services
        $services = $this->serviceManager->getServicesList();

        // check if services list is iterable
        if (!is_iterable($services)) {
            $io->error('error to iterate services list');
            return;
        }

        // handle configured services status
        foreach ($services as $service) {
            // force retype service array (to avoid phpstan error)
            $service = (array) $service;

            // check if monitoring is disabled
            if ($this->cacheUtil->isCatched('monitoring-disabler-' . $service['service_name'])) {
                $io->writeln(
                    '[' . date('Y-m-d H:i:s') . '] monitoring: <fg=yellow>monitoring skipped for service: ' . $service['service_name'] . '</fg=yellow>'
                );
                continue;
            }

            // check if service is enabled
            if ($service['monitoring'] == false) {
                continue;
            }

            // check systemd service status
            if ($service['type'] == 'systemd') {
                // check running state
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
                    $io->writeln(
                        '[' . date('Y-m-d H:i:s') . '] monitoring: <fg=red>' . $service['display_name'] . ' is not running</fg=red>'
                    );
                }
            }

            // check http service status
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

                        // check if metrics should be collected
                        if ($service['metrics_monitoring']['collect_metrics'] == 'true') {
                            // get metrics from metrics collector
                            $metrics = $this->jsonUtil->getJson($service['metrics_monitoring']['metrics_collector_url']);

                            // check if metrics get is successful
                            if ($metrics == null) {
                                // handle error
                                $errorMessage = 'error to get metrics from ' . $service['metrics_monitoring']['metrics_collector_url'];
                                $io->error($errorMessage);
                                $this->errorManager->logError(
                                    message: $errorMessage,
                                    code: Response::HTTP_INTERNAL_SERVER_ERROR
                                );
                            } else {
                                // collect all metrics
                                foreach ($metrics as $name => $value) {
                                    try {
                                        $metricSaveStatus = $this->metricsManager->saveServiceMetric(
                                            metricName: $name,
                                            value: $value,
                                            serviceName: $service['service_name']
                                        );
                                        if ($metricSaveStatus) {
                                            $io->writeln(
                                                '[' . date('Y-m-d H:i:s') . '] monitoring: <fg=green>metric ' . $name . ' from service ' . $service['service_name'] . ' saved</fg=green>'
                                            );
                                        }
                                    } catch (Exception $e) {
                                        $this->errorManager->logError(
                                            message: $e->getMessage(),
                                            code: Response::HTTP_INTERNAL_SERVER_ERROR
                                        );
                                    }
                                }
                            }
                        }
                    }

                // service is not online
                } else {
                    $this->handleMonitoringStatus(
                        serviceName: $service['service_name'],
                        currentStatus: 'not-online',
                        message: $service['display_name'] . ' is offline'
                    );
                    $io->writeln(
                        '[' . date('Y-m-d H:i:s') . '] monitoring: <fg=red>' . $service['display_name'] . ' is offline</fg=red>'
                    );
                }
            }
        }

        // save host usages metrics to database
        try {
            $this->metricsManager->saveUsageMetrics($cpuUsage, $ramUsage, $storageUsage);
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
