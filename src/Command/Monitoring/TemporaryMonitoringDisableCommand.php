<?php

namespace App\Command\Monitoring;

use Exception;
use App\Util\AppUtil;
use App\Manager\MonitoringManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class TemporaryMonitoringDisableCommand
 *
 * Command to disable monitoring for a service for a specific time
 *
 * @package App\Command
 */
#[AsCommand(name: 'app:monitoring:temporary:disable', description: 'Temporary disable monitoring process')]
class TemporaryMonitoringDisableCommand extends Command
{
    private AppUtil $appUtil;
    private MonitoringManager $monitoringManager;

    public function __construct(AppUtil $appUtil, MonitoringManager $monitoringManager)
    {
        $this->appUtil = $appUtil;
        $this->monitoringManager = $monitoringManager;
        parent::__construct();
    }

    /**
     * Configure command arguments
     *
     * @return void
     */
    protected function configure(): void
    {
        $this->addArgument('service-name', InputArgument::REQUIRED, 'Service name to disable monitoring');
        $this->addArgument('time', InputArgument::REQUIRED, 'Time to disable monitoring (in minutes)');
    }

    /**
     * Execute command to disable monitoring for a service for a specific time
     *
     * @param InputInterface $input The input interface
     * @param OutputInterface $output The output interface
     *
     * @return int The command exit code
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // check if monitoring future is disabled
        if ($this->appUtil->isFeatureFlagDisabled('monitoring')) {
            $io->error('Monitoring future is disabled');
            return Command::FAILURE;
        }

        // get cli arguments
        $serviceName = $input->getArgument('service-name');
        $time = $input->getArgument('time');

        // check if service name is empty
        if (empty($serviceName)) {
            $io->error('Service name parameter is required');
            return Command::FAILURE;
        }

        // check if time is empty
        if (empty($time)) {
            $io->error('Time parameter is required');
            return Command::FAILURE;
        }

        // check if time is numeric
        if (!is_numeric($time)) {
            $io->error('Time parameter must be numeric');
            return Command::FAILURE;
        }
        $time = (int) $time;

        // check if time is positive
        if ($time <= 0) {
            $io->error('Time parameter must be positive');
            return Command::FAILURE;
        }

        // check if service name is valid
        if (!is_string($serviceName)) {
            $io->error('Invalid service name provided');
            return Command::FAILURE;
        }

        // check if service is not already disabled
        if ($this->monitoringManager->getMonitoringStatus($serviceName) == 'disabled') {
            $io->error('Service is already disabled');
            return Command::FAILURE;
        }

        // disable monitoring for service
        try {
            $this->monitoringManager->temporaryDisableMonitoring($serviceName, $time);
            $io->success('Service monitoring is disabled for ' . $time . ' minutes');
        } catch (Exception $e) {
            $io->error('Error to disable service monitoring: ' . $e->getMessage());
            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}
