<?php

namespace App\Manager;

use DateTime;
use Exception;
use App\Util\AppUtil;
use App\Util\ServerUtil;
use App\Entity\MonitoringStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
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
    private LogManager $logManager;
    private ServerUtil $serverUtil;
    private EmailManager $emailManager;
    private ErrorManager $errorManager;
    private ServiceManager $serviceManager;
    private NotificationsManager $notificationsManager;
    private EntityManagerInterface $entityManagerInterface;

    public function __construct(
        AppUtil $appUtil,
        LogManager $logManager,
        ServerUtil $serverUtil,
        EmailManager $emailManager,
        ErrorManager $errorManager,
        ServiceManager $serviceManager,
        NotificationsManager $notificationsManager,
        EntityManagerInterface $entityManagerInterface
    ) {
        $this->appUtil = $appUtil;
        $this->logManager = $logManager;
        $this->serverUtil = $serverUtil;
        $this->emailManager = $emailManager;
        $this->errorManager = $errorManager;
        $this->serviceManager = $serviceManager;
        $this->notificationsManager = $notificationsManager;
        $this->entityManagerInterface = $entityManagerInterface;
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
            return $this->entityManagerInterface->getRepository(MonitoringStatus::class)->findOneBy($search);
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
        $MonitoringStatus = $this->getMonitoringStatusRepository(['service_name' => $serviceName]);

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
            $repo = $this->getMonitoringStatusRepository(['service_name' => $serviceName]);

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
                $message,
                $currentStatus
            );

            // send monitoring status notification
            $this->notificationsManager->sendNotification('monitoring ' . $serviceName, $message);

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
                message: 'Mysql server down detected',
                currentStatus: 'critical'
            );
            // send push notification
            $this->notificationsManager->sendNotification('monitoring', 'Mysql server down detected');
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
                message: 'error to init monitoring process: this method can only be called from cli',
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        // monitor cpu usage
        if ($this->serverUtil->getCpuUsage() > 98) {
            $this->handleMonitoringStatus(
                serviceName: 'system-cpu-usage',
                currentStatus: 'critical',
                message: 'cpu usage is too high'
            );
            $io->writeln(
                '[' . date('Y-m-d H:i:s') . '] monitoring: <fg=red>cpu usage is too high</fg=red>'
            );
        } else {
            $this->handleMonitoringStatus(
                serviceName: 'system-cpu-usage',
                currentStatus: 'ok',
                message: 'cpu usage is ok'
            );
            $io->writeln(
                '[' . date('Y-m-d H:i:s') . '] monitoring: <fg=green>cpu usage is ok</fg=green>'
            );
        }

        // monitor ram usage
        if ($this->serverUtil->getRamUsagePercentage() > 98) {
            $this->handleMonitoringStatus(
                serviceName: 'system-ram-usage',
                currentStatus: 'critical',
                message: 'ram usage is too high'
            );
            $io->writeln(
                '[' . date('Y-m-d H:i:s') . '] monitoring: <fg=red>ram usage is too high</fg=red>'
            );
        } else {
            $this->handleMonitoringStatus(
                serviceName: 'system-ram-usage',
                currentStatus: 'ok',
                message: 'ram usage is ok'
            );
            $io->writeln(
                '[' . date('Y-m-d H:i:s') . '] monitoring: <fg=green>ram usage is ok</fg=green>'
            );
        }

        // monitor storage usage
        if ($this->serverUtil->getDriveUsagePercentage() > 98) {
            $this->handleMonitoringStatus(
                serviceName: 'system-storage-usage',
                currentStatus: 'critical',
                message: 'storage space is too low'
            );
            $io->writeln(
                '[' . date('Y-m-d H:i:s') . '] monitoring: <fg=red>storage space is too low/fg=red>'
            );
        } else {
            $this->handleMonitoringStatus(
                serviceName: 'system-storage-usage',
                currentStatus: 'ok',
                message: 'storage space is ok'
            );
            $io->writeln(
                '[' . date('Y-m-d H:i:s') . '] monitoring: <fg=green>storage space is ok</fg=green>'
            );
        }

        // get monitored services
        $services = $this->serviceManager->getServicesList();

        // check services status
        if (is_iterable($services)) {
            foreach ($services as $service) {
                // force retype service array (to avoid phpstan error)
                $service = (array) $service;

                // check if service is enabled
                if ($service['enable'] == false) {
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
                        if ($serviceStatus['responseCode'] != $service['accept_code']) {
                            $this->handleMonitoringStatus(
                                serviceName: $service['service_name'],
                                currentStatus: 'not-accepting-code',
                                message: $service['display_name'] . ' is not accepting code ' . $service['accept_code'] . ' (response code: ' . $serviceStatus['responseCode'] . ')'
                            );
                            $io->writeln(
                                '[' . date('Y-m-d H:i:s') . '] monitoring: <fg=red>' . $service['display_name'] . ' is not accepting code ' . $service['accept_code'] . ' (response code: ' . $serviceStatus['responseCode'] . ')</fg=red>'
                            );

                        // check service response time
                        } elseif ($serviceStatus['responseTime'] > $service['max_response_time']) {
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
        } else {
            $io->error('error to iterate services list');
        }
    }
}
