<?php

namespace App\Command;

use Exception;
use App\Manager\ServiceManager;
use App\Manager\MonitoringManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class DisableNextMonitoringCommand
 *
 * Command to disable next monitoring
 *
 * @package App\Command
 */
#[AsCommand(name: 'app:monitoring:disable', description: 'disable next monitoring')]
class DisableNextMonitoringCommand extends Command
{
    private ServiceManager $serviceManager;
    private MonitoringManager $monitoringManager;

    public function __construct(ServiceManager $serviceManager, MonitoringManager $monitoringManager)
    {
        $this->serviceManager = $serviceManager;
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
        $this->addArgument('time', InputArgument::OPTIONAL, 'time for disabling next monitoring (in minutes)');
    }

    /**
     * Execute command to disable next monitoring
     *
     * @param InputInterface $input The input interface
     * @param OutputInterface $output The output interface
     *
     * @return int Command status code
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // fix get CLI visitor info
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['HTTP_USER_AGENT'] = 'console';

        // get time argument
        $time = (int) $input->getArgument('time');

        // check if time is empty
        if (empty($time)) {
            $time = 5;
        }

        try {
            // get services list
            $services = $this->serviceManager->getServicesList();

            // check if services list is iterable
            if (!is_iterable($services)) {
                $io->error('error to get services list');
                return Command::FAILURE;
            }

            // disable monitoring for all services
            foreach ($services as $service) {
                // check if monitoring is disabled
                if ($service['monitoring'] == false) {
                    continue;
                }

                // disable next monitoring for service
                $this->monitoringManager->disableNextMonitoring($service['service_name'], $time);
            }
            $io->success('next monitoring disabled for all services (for next ' . $time . ' minutes)');
        } catch (Exception $e) {
            $io->error('Error to disable next monitoring: ' . $e->getMessage());
            return Command::FAILURE;
        }
        return Command::SUCCESS;
    }
}
