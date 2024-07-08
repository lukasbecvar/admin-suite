<?php

namespace App\Command;

use App\Util\AppUtil;
use App\Util\ServerUtil;
use App\Manager\ServiceManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class MonitroingProcessCommand
 *
 * Command to monitoring services
 *
 * @package App\Command
 */
#[AsCommand(name: 'app:process:monitroing', description: 'Main service monitoring process loop')]
class MonitroingProcessCommand extends Command
{
    private AppUtil $appUtil;
    private ServerUtil $serverUtil;
    private ServiceManager $serviceManager;

    public function __construct(AppUtil $appUtil, ServerUtil $serverUtil, ServiceManager $serviceManager)
    {
        $this->appUtil = $appUtil;
        $this->serverUtil = $serverUtil;
        $this->serviceManager = $serviceManager;
        parent::__construct();
    }

    /**
     * Executes the command to monitoring services
     *
     * @param InputInterface $input The input interface
     * @param OutputInterface $output The output interface
     *
     * @return int The exit code of the command
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // set up signal handling to allow termination via SIGINT (Ctrl+C)
        pcntl_signal(SIGINT, function () use ($io) {
            $io->info('monitoring process terminated.');
            exit(0);
        });

        /** @phpstan-ignore-next-line */
        while (true) {
            
            // monitor cpu usage
            if ($this->serverUtil->getCpuUsage() > 95) {
                $io->writeln('[' . date('Y-m-d H:i:s') . '] <fg=red>cpu usage is too high</fg=red>');
            } else {
                $io->writeln('[' . date('Y-m-d H:i:s') . '] <fg=green>cpu usage is ok</fg=green>');
            }

            // monitor ram usage
            if ($this->serverUtil->getRamUsagePercentage() > 95) {
                $io->writeln('[' . date('Y-m-d H:i:s') . '] <fg=red>ram usage is too high</fg=red>');
            } else {
                $io->writeln('[' . date('Y-m-d H:i:s') . '] <fg=green>ram usage is ok</fg=green>');
            }

            // monitor disk usage
            if ($this->serverUtil->getDriveUsagePercentage() > 95) {
                $io->writeln('[' . date('Y-m-d H:i:s') . '] <fg=red>disk usage is too high</fg=red>');
            } else {
                $io->writeln('[' . date('Y-m-d H:i:s') . '] <fg=green>disk usage is ok</fg=green>');
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
                            $io->writeln('[' . date('Y-m-d H:i:s') . '] <fg=green>' . $service['display_name'] . ' is running</fg=green>');
                        } else {
                            $io->writeln('[' . date('Y-m-d H:i:s') . '] <fg=red>' . $service['display_name'] . ' is not running</fg=red>');
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
                                $io->writeln('[' . date('Y-m-d H:i:s') . '] <fg=red>' . $service['display_name'] . ' is not accepting code ' . $service['accept_code'] . '</fg=red>');

                                // check service response time
                            } elseif ($serviceStatus['responseTime'] > $service['max_response_time']) {
                                $io->writeln('[' . date('Y-m-d H:i:s') . '] <fg=red>' . $service['display_name'] . ' is not responding in ' . $service['max_response_time'] . '</fg=red>');

                                // status ok
                            } else {
                                $io->writeln('[' . date('Y-m-d H:i:s') . '] <fg=green>' . $service['display_name'] . ' is online</fg=green>');
                            }

                            // service is not online
                        } else {
                            $io->writeln('[' . date('Y-m-d H:i:s') . '] <fg=red>' . $service['display_name'] . ' is not online</fg=red>');
                        }
                    }
                }
            } else {
                $io->error('error to iterate services list');
            }

            // sleep monitoring interval
            sleep($this->appUtil->getMonitroingInterval() * 60);
        }

        /** @phpstan-ignore-next-line */
        return Command::SUCCESS;
    }
}
