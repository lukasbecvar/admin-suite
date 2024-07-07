<?php

namespace App\Command;

use App\Util\AppUtil;
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
    private ServiceManager $serviceManager;

    public function __construct(AppUtil $appUtil, ServiceManager $serviceManager)
    {
        $this->appUtil = $appUtil;
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

        // get monitored services
        $services = $this->serviceManager->getServicesList();

        // set up signal handling to allow termination via SIGINT (Ctrl+C)
        pcntl_signal(SIGINT, function () use ($io) {
            $io->info('Monitoring process terminated.');
            exit(0);
        });

        // monitoring loop
        /** @phpstan-ignore-next-line */
        while (true) {
            // check services status
            if (is_iterable($services)) {
                foreach ($services as $service) {
                    $service = (array) $service;

                    // check if service is systemd
                    if ($service['type'] == 'systemd') {
                        // check if service is running
                        if ($this->serviceManager->isServiceRunning($service['service_name'])) {
                            $io->success($service['display_name'] . ' is running');
                        } else {
                            $io->error($service['display_name'] . ' is not running');
                        }
                    }
                }
            }

            // sleep for monitroing interval
            sleep($this->appUtil->getMonitroingInterval() * 60);
        }

        /** @phpstan-ignore-next-line */
        return Command::SUCCESS;
    }
}
