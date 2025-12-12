<?php

namespace App\Manager;

use DateTime;
use Exception;
use App\Util\AppUtil;
use App\Util\CacheUtil;
use App\Util\ServerUtil;
use App\Entity\SLAHistory;
use App\Util\MonitoringUtil;
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
    private CacheUtil $cacheUtil;
    private LogManager $logManager;
    private ServerUtil $serverUtil;
    private EmailManager $emailManager;
    private ErrorManager $errorManager;
    private MetricsManager $metricsManager;
    private ServiceManager $serviceManager;
    private MonitoringUtil $monitoringUtil;
    private SLAHistoryRepository $slaHistoryRepository;
    private NotificationsManager $notificationsManager;
    private EntityManagerInterface $entityManagerInterface;
    private MonitoringStatusRepository $monitoringStatusRepository;

    public function __construct(
        AppUtil $appUtil,
        CacheUtil $cacheUtil,
        LogManager $logManager,
        ServerUtil $serverUtil,
        EmailManager $emailManager,
        ErrorManager $errorManager,
        MetricsManager $metricsManager,
        ServiceManager $serviceManager,
        MonitoringUtil $monitoringUtil,
        SLAHistoryRepository $slaHistoryRepository,
        NotificationsManager $notificationsManager,
        EntityManagerInterface $entityManagerInterface,
        MonitoringStatusRepository $monitoringStatusRepository
    ) {
        $this->appUtil = $appUtil;
        $this->cacheUtil = $cacheUtil;
        $this->logManager = $logManager;
        $this->serverUtil = $serverUtil;
        $this->emailManager = $emailManager;
        $this->errorManager = $errorManager;
        $this->metricsManager = $metricsManager;
        $this->serviceManager = $serviceManager;
        $this->monitoringUtil = $monitoringUtil;
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
     * Get latest monitoring status snapshot for all services
     *
     * @return array<int, array<string, mixed>> The monitoring status snapshot
     */
    public function getMonitoringStatusSnapshot(): array
    {
        try {
            /** @var array<MonitoringStatus> $statuses */
            $statuses = $this->monitoringStatusRepository->findAll();
        } catch (Exception $e) {
            $this->errorManager->handleError(
                message: 'error to get monitoring statuses: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        $snapshot = [];
        $totalMinutesInMonth = 30.44 * 24 * 60;

        foreach ($statuses ?? [] as $status) {
            $downTime = $status->getDownTime() ?? 0;
            $slaValue = null;

            if ($downTime != null) {
                $slaValue = round((1 - ($downTime / $totalMinutesInMonth)) * 100, 2);
            }

            $snapshot[] = [
                'service_name' => (string) $status->getServiceName(),
                'status' => (string) $status->getStatus(),
                'message' => (string) $status->getMessage(),
                'down_time_minutes' => $downTime,
                'sla_timeframe' => (string) $status->getSlaTimeframe(),
                'last_update_time' => $status->getLastUpdateTime() ? $status->getLastUpdateTime()->format(DATE_ATOM) : null,
                'current_sla' => $slaValue
            ];
        }

        return $snapshot;
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
                key: 'monitoring-status-' . $serviceName,
                value: $currentStatus,
                expiration: ($monitoringInterval + ($monitoringInterval / 2))
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
                $serviceName = $slaHistory->getServiceName() ?? 'Unknown Service';
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
     * Monitor system resources (CPU, RAM, Storage)
     *
     * @param SymfonyStyle $io The Symfony command output decorator
     * @param float $cpuUsage The current CPU usage
     * @param float $ramUsage The current RAM usage
     * @param int $storageUsage The current storage usage
     *
     * @return void
     */
    public function monitorSystemResources(SymfonyStyle $io, float $cpuUsage, float $ramUsage, int $storageUsage): void
    {
        // monitor cpu usage
        if ($cpuUsage > 99) {
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
        if ($ramUsage > 99) {
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
    }

    /**
     * Monitor configured services
     *
     * @param SymfonyStyle $io The Symfony command output decorator
     * @param array<array<mixed>> $services The list of services
     * @param int $possibleDownTime The possible down time in minutes
     *
     * @return void
     */
    public function monitorServices(SymfonyStyle $io, array $services, int $possibleDownTime): void
    {
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

            switch ($service['type']) {
                case 'systemd':
                    $this->monitoringUtil->monitorSystemdService($io, $service, $possibleDownTime, $this);
                    break;
                case 'http':
                    $this->monitoringUtil->monitorHttpService($io, $service, $possibleDownTime, $this);
                    break;
                default:
                    // log an error for unknown service types
                    $serviceName = $service['display_name'] ?? ($service['service_name'] ?? 'unknown service');
                    $this->errorManager->logError(
                        message: 'service ' . $serviceName . ' has unknown type: ' . $service['type'],
                        code: Response::HTTP_INTERNAL_SERVER_ERROR
                    );
            }
        }
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

        /** @var array<array<mixed>> $services */
        $services = $this->serviceManager->getServicesList();
        if (!is_iterable($services)) {
            $io->error('Error to iterate services list');
            return;
        }

        // get current resource usages
        $cpuUsage = $this->serverUtil->getCpuUsage();
        $ramUsage = $this->serverUtil->getRamUsagePercentage();
        $storageUsage = (int) $this->serverUtil->getDriveUsagePercentage();
        $networkStats = $this->serverUtil->getNetworkStats();

        // monitor system resources
        $this->monitorSystemResources($io, $cpuUsage, $ramUsage, $storageUsage);

        // handle configured services status
        $this->monitorServices($io, $services, $possibleDownTime);

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
