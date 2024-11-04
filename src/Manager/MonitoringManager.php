<?php

namespace App\Manager;

use DateTime;
use Exception;
use App\Util\AppUtil;
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
 * The manager for methos associated with monitoring process
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
    private ServiceManager $serviceManager;
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
        ServiceManager $serviceManager,
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
     * @throws Exception If an error occurs while retrieving the repository
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
     * @throws Exception If an error occurs while setting the status
     *
     * @return void
     */
    public function setMonitoringStatus(string $serviceName, string $message, string $status): void
    {
        // get service monitoring repository
        $MonitoringStatus = $this->monitoringStatusRepository->findOneBy(['service_name' => $serviceName]);

        // check if service monitoring repository is found
        if ($MonitoringStatus == null) {
            $MonitoringStatus = new MonitoringStatus();

            // set monitored service properties
            $MonitoringStatus->setServiceName($serviceName)
                ->setStatus($status)
                ->setMessage('new service initialization')
                ->setLastUpdateTime(new DateTime());

            // persist service monitoring
            try {
                $this->entityManagerInterface->persist($MonitoringStatus);
            } catch (Exception $e) {
                $this->errorManager->handleError(
                    message: 'error to persist service monitoring: ' . $e->getMessage(),
                    code: Response::HTTP_INTERNAL_SERVER_ERROR
                );
            }
        } else {
            // update service monitoring properties
            $MonitoringStatus->setMessage($message)
                ->setStatus($status)
                ->setLastUpdateTime(new DateTime());
        }

        // flush changes to database
        try {
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
     * @throws Exception If an error occurs while retrieving the status
     *
     * @return string|null The service monitoring status
     */
    public function getMonitoringStatus(string $serviceName): ?string
    {
        try {
            /** @var \App\Entity\MonitoringStatus $repo the monitored service repository */
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
            $this->notificationsManager->sendNotification('monitoring ' . $serviceName, '[' . date('Y-m-d H:i:s') . ']: ' . $message);

            // log status chnage
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
            // send push notification
            $this->notificationsManager->sendNotification('monitoring', 'Mysql server is down');
        }

        // print database is down message
        $io->writeln('[' . date('Y-m-d H:i:s') . '] monitoring: <fg=red>database is down</fg=red>');
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

        // get monitoring interval
        $monitoringInterval = (int) $this->appUtil->getEnvValue('MONITORING_INTERVAL') * 60;

        // monitor cpu usage
        if ($this->serverUtil->getCpuUsage() > 98) {
            // check if status is critical multiple times
            if ($this->cacheUtil->isCatched('critical-cpu-usage')) {
                dump('critical-cpu-usage');
                $this->handleMonitoringStatus(
                    serviceName: 'system-cpu-usage',
                    currentStatus: 'critical',
                    message: 'cpu usage is too high'
                );
            }

            // cache cpu usage status
            $this->cacheUtil->setValue('critical-cpu-usage', 'critical', ($monitoringInterval + ($monitoringInterval / 2)));

            // log status to console output
            $io->writeln(
                '[' . date('Y-m-d H:i:s') . '] monitoring: <fg=red>cpu usage is too high</fg=red>'
            );
        } else {
            $this->handleMonitoringStatus(
                serviceName: 'system-cpu-usage',
                currentStatus: 'normal',
                message: 'cpu usage is normal'
            );
            $io->writeln(
                '[' . date('Y-m-d H:i:s') . '] monitoring: <fg=green>cpu usage is normal</fg=green>'
            );
        }

        // monitor ram usage
        if ($this->serverUtil->getRamUsagePercentage() > 98) {
            // check if status is critical multiple times
            if ($this->cacheUtil->isCatched('critical-ram-usage')) {
                $this->handleMonitoringStatus(
                    serviceName: 'system-ram-usage',
                    currentStatus: 'critical',
                    message: 'ram usage is too high'
                );
            }

            // cache ram usage status
            $this->cacheUtil->setValue('critical-ram-usage', 'critical', ($monitoringInterval + ($monitoringInterval / 2)));

            $io->writeln(
                '[' . date('Y-m-d H:i:s') . '] monitoring: <fg=red>ram usage is too high</fg=red>'
            );
        } else {
            $this->handleMonitoringStatus(
                serviceName: 'system-ram-usage',
                currentStatus: 'normal',
                message: 'ram usage is normal'
            );
            $io->writeln(
                '[' . date('Y-m-d H:i:s') . '] monitoring: <fg=green>ram usage is normal</fg=green>'
            );
        }

        // monitor storage usage
        if ($this->serverUtil->getDriveUsagePercentage() > 98) {
            // check if status is critical multiple times
            if ($this->cacheUtil->isCatched('critical-storage-usage')) {
                $this->handleMonitoringStatus(
                    serviceName: 'system-storage-usage',
                    currentStatus: 'critical',
                    message: 'storage space on the disk is not enough'
                );
            }

            // cache storage usage status
            $this->cacheUtil->setValue('critical-storage-usage', 'critical', ($monitoringInterval + ($monitoringInterval / 2)));

            $io->writeln(
                '[' . date('Y-m-d H:i:s') . '] monitoring: <fg=red>storage space on the disk is not enough</fg=red>'
            );
        } else {
            $this->handleMonitoringStatus(
                serviceName: 'system-storage-usage',
                currentStatus: 'normal',
                message: 'storage space is normal'
            );
            $io->writeln(
                '[' . date('Y-m-d H:i:s') . '] monitoring: <fg=green>storage space is normal</fg=green>'
            );
        }

        // get monitored services
        $services = $this->serviceManager->getServicesList();

        // check if services list is iterable
        if (!is_iterable($services)) {
            $io->error('error to iterate services list');
            return;
        }

        foreach ($services as $service) {
            // force retype service array (to avoid phpstan error)
            $service = (array) $service;

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
                        '[' . date('Y-m-d H:i:s') . '] monitoring: <fg=green>' . $service['display_name'] . ' is running</fg=green>'
                    );
                } else {
                    // check if status is critical multiple times
                    if ($this->cacheUtil->isCatched($service['service_name'] . '-not-running')) {
                        $this->handleMonitoringStatus(
                            serviceName: $service['service_name'],
                            currentStatus: 'not-running',
                            message: $service['display_name'] . ' is not running'
                        );
                    }

                    // cache not running status
                    $this->cacheUtil->setValue($service['service_name'] . '-not-running', 'critical', ($monitoringInterval + ($monitoringInterval / 2)));

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

                        // check if status is critical multiple times
                        if ($this->cacheUtil->isCatched($service['service_name'] . '-not-accepting-code')) {
                            $this->handleMonitoringStatus(
                                serviceName: $service['service_name'],
                                currentStatus: 'not-accepting-code',
                                message: $service['display_name'] . ' is not accepting any of the codes ' . $acceptCodesStr . ' (response code: ' . $serviceStatus['responseCode'] . ')'
                            );
                        }

                        // cache not running status
                        $this->cacheUtil->setValue($service['service_name'] . '-not-accepting-code', 'critical', ($monitoringInterval + ($monitoringInterval / 2)));

                        $io->writeln(
                            '[' . date('Y-m-d H:i:s') . '] monitoring: <fg=red>' . $service['display_name'] . ' is not accepting any of the codes ' . $acceptCodesStr . ' (response code: ' . $serviceStatus['responseCode'] . ')</fg=red>'
                        );
                    // check service response time
                    } elseif ($serviceStatus['responseTime'] > $service['max_response_time']) {
                        // check if status is critical multiple times
                        if ($this->cacheUtil->isCatched($service['service_name'] . '-not-responding')) {
                            $this->handleMonitoringStatus(
                                serviceName: $service['service_name'],
                                currentStatus: 'not-responding',
                                message: $service['display_name'] . ' is not responding in ' . $service['max_response_time'] . 'ms'
                            );
                        }

                        // cache not responding status
                        $this->cacheUtil->setValue($service['service_name'] . '-not-responding', 'critical', ($monitoringInterval + ($monitoringInterval / 2)));

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
                            '[' . date('Y-m-d H:i:s') . '] monitoring: <fg=green>' . $service['display_name'] . ' is online</fg=green>'
                        );
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

        // calculate last monitoring time expiration
        $lastMonitoringTimeExpiration = (intval($monitoringInterval) * 60) * 2;

        // save last monitoring time to cache
        $this->cacheUtil->setValue('last-monitoring-time', new DateTime(), $lastMonitoringTimeExpiration);
    }
}
