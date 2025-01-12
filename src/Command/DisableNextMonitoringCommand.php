<?php

namespace App\Command;

use Exception;
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
    private MonitoringManager $monitoringManager;

    public function __construct(MonitoringManager $monitoringManager)
    {
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
        $this->addArgument('time', InputArgument::OPTIONAL, 'time for disabling next monitoring (in minutes)', 5);
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

        // get time argument from command arguments
        $time = (int) $input->getArgument('time');

        try {
            $this->monitoringManager->disableNextMonitoring('complete-monitoring-job', $time);
            $io->success('Next monitoring disabled (time: ' . $time . ' minutes)');
            return Command::SUCCESS;
        } catch (Exception $e) {
            $io->error('Error to disable monitoring process: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
