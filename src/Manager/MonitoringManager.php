<?php

namespace App\Manager;

use App\Util\AppUtil;
use App\Util\ServerUtil;
use App\Entity\ServiceMonitoring;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Class MonitoringManager
 *
 * The manager for methos associated with monitroing process
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
    private EntityManagerInterface $entityManagerInterface;

    public function __construct(AppUtil $appUtil, LogManager $logManager, ServerUtil $serverUtil, EmailManager $emailManager, ErrorManager $errorManager, ServiceManager $serviceManager, EntityManagerInterface $entityManagerInterface)
    {
        $this->appUtil = $appUtil;
        $this->logManager = $logManager;
        $this->serverUtil = $serverUtil;
        $this->emailManager = $emailManager;
        $this->errorManager = $errorManager;
        $this->serviceManager = $serviceManager;
        $this->entityManagerInterface = $entityManagerInterface;
    }

    /**
     * Get service monitoring repository
     *
     * @param array<mixed> $search The search parameters
     *
     * @return ServiceMonitoring|null The service monitoring repository
     */
    public function getServiceMonitoringRepository(array $search): ?ServiceMonitoring
    {
        try {
            return $this->entityManagerInterface->getRepository(ServiceMonitoring::class)->findOneBy($search);
        } catch (\Exception $e) {
            $this->errorManager->handleError('error to get service monitoring repository', Response::HTTP_INTERNAL_SERVER_ERROR);
            return null;
        }
    }

    /**
     * Set monitoring status for a service
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
        $serviceMonitoring = $this->getServiceMonitoringRepository(['service_name' => $serviceName]);

        // check if service monitoring repository is found
        if ($serviceMonitoring == null) {
            $serviceMonitoring = new ServiceMonitoring();

            // set monitored service properties
            $serviceMonitoring->setServiceName($serviceName)
                ->setStatus($status)
                ->setMessage('new service initialization')
                ->setLastUpdateTime(new \DateTime());

            // persist service monitoring
            try {
                $this->entityManagerInterface->persist($serviceMonitoring);
            } catch (\Exception $e) {
                $this->errorManager->handleError('error to persist service monitoring: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        } else {
            // update service monitoring properties
            $serviceMonitoring->setMessage($message)
                ->setStatus($status)
                ->setLastUpdateTime(new \DateTime());
        }

        // flush changes to database
        try {
            $this->entityManagerInterface->flush();
        } catch (\Exception $e) {
            $this->errorManager->handleError('error to flush service monitoring: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Get monitoring status for a service
     *
     * @param string $serviceName The name of the service
     *
     * @return string|null The service monitoring status
     */
    public function getMonitoringStatus(string $serviceName): ?string
    {
        try {
            /** @var \App\Entity\ServiceMonitoring $repo */
            $repo = $this->getServiceMonitoringRepository(['service_name' => $serviceName]);

            // check if repository is found
            if ($repo != null) {
                return $repo->getStatus();
            }

            return null;
        } catch (\Exception $e) {
            $this->errorManager->handleError('error to get service status: ' . $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
            return null;
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
            // check if last status is pending
            if ($lastStatus == 'pending') {
                // send monitoring status email
                $this->emailManager->sendEmail($this->appUtil->getAdminContactEmail(), 'monitoring status', ['serviceName' => $serviceName, 'monitoringMesssage' => $message, 'monitoringStatus' => $currentStatus], 'monitoring-status');

                // log status chnage
                $this->logManager->log('monitoring', 'Service: ' . $serviceName . ' status changed to: ' . $currentStatus . ' message: ' . $message, 2);

                // update monitoring status
                $this->setMonitoringStatus($serviceName, $message, $currentStatus);
            } else {
                // set pending status
                $this->setMonitoringStatus($serviceName, $message, 'pending');
            }
        }
    }

    /**
     * Init monitroing process (called from monitroing process command)
     *
     * @param SymfonyStyle $io The io interface
     *
     * @return void
     */
    public function monitorInit(SymfonyStyle $io): void
    {
        // check if method is called from cli
        if (php_sapi_name() != 'cli') {
            $this->errorManager->handleError('error to init monitroing process: this method can only be called from cli', Response::HTTP_INTERNAL_SERVER_ERROR);
        }

        // monitor cpu usage
        if ($this->serverUtil->getCpuUsage() > 95) {
            $this->handleMonitoringStatus('system-cpu-usage', 'critical', 'cpu usage is too high');
            $io->writeln('[' . date('Y-m-d H:i:s') . '] monitoring: <fg=red>cpu usage is too high</fg=red>');
        } else {
            $this->handleMonitoringStatus('system-cpu-usage', 'ok', 'cpu usage is ok');
            $io->writeln('[' . date('Y-m-d H:i:s') . '] monitoring: <fg=green>cpu usage is ok</fg=green>');
        }

        // monitor ram usage
        if ($this->serverUtil->getRamUsagePercentage() > 95) {
            $this->handleMonitoringStatus('system-ram-usage', 'critical', 'ram usage is too high');
            $io->writeln('[' . date('Y-m-d H:i:s') . '] monitoring: <fg=red>ram usage is too high</fg=red>');
        } else {
            $this->handleMonitoringStatus('system-ram-usage', 'ok', 'ram usage is ok');
            $io->writeln('[' . date('Y-m-d H:i:s') . '] monitoring: <fg=green>ram usage is ok</fg=green>');
        }

        // monitor disk usage
        if ($this->serverUtil->getDriveUsagePercentage() > 95) {
            $this->handleMonitoringStatus('system-disk-usage', 'critical', 'disk space is too low');
            $io->writeln('[' . date('Y-m-d H:i:s') . '] monitoring: <fg=red>disk space is too low/fg=red>');
        } else {
            $this->handleMonitoringStatus('system-disk-usage', 'ok', 'disk space is ok');
            $io->writeln('[' . date('Y-m-d H:i:s') . '] monitoring: <fg=green>disk space is ok</fg=green>');
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
                        $this->handleMonitoringStatus($service['service_name'], 'running', $service['display_name'] . ' is running');
                        $io->writeln('[' . date('Y-m-d H:i:s') . '] monitoring: <fg=green>' . $service['display_name'] . ' is running</fg=green>');
                    } else {
                        $this->handleMonitoringStatus($service['service_name'], 'not-running', $service['display_name'] . ' is not running');
                        $io->writeln('[' . date('Y-m-d H:i:s') . '] monitoring: <fg=red>' . $service['display_name'] . ' is not running</fg=red>');
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
                            $this->handleMonitoringStatus($service['service_name'], 'not-accepting-code', $service['display_name'] . ' is not accepting code ' . $service['accept_code']);
                            $io->writeln('[' . date('Y-m-d H:i:s') . '] monitoring: <fg=red>' . $service['display_name'] . ' is not accepting code ' . $service['accept_code'] . '</fg=red>');

                        // check service response time
                        } elseif ($serviceStatus['responseTime'] > $service['max_response_time']) {
                            $this->handleMonitoringStatus($service['service_name'], 'not-responding', $service['display_name'] . ' is not responding in ' . $service['max_response_time']);
                            $io->writeln('[' . date('Y-m-d H:i:s') . '] monitoring: <fg=red>' . $service['display_name'] . ' is not responding in ' . $service['max_response_time'] . '</fg=red>');

                        // status ok
                        } else {
                            $this->handleMonitoringStatus($service['service_name'], 'online', $service['display_name'] . ' is online');
                            $io->writeln('[' . date('Y-m-d H:i:s') . '] monitoring: <fg=green>' . $service['display_name'] . ' is online</fg=green>');
                        }

                    // service is not online
                    } else {
                        $this->handleMonitoringStatus($service['service_name'], 'not-online', $service['display_name'] . ' is offline');
                        $io->writeln('[' . date('Y-m-d H:i:s') . '] monitoring: <fg=red>' . $service['display_name'] . ' is offline</fg=red>');
                    }
                }
            }
        } else {
            $io->error('error to iterate services list');
        }
    }
}
