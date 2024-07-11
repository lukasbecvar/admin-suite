<?php

namespace App\Command\Process;

use App\Util\AppUtil;
use App\Manager\DatabaseManager;
use App\Manager\MonitoringManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class MonitoringProcessCommand
 *
 * Command to monitoring services
 *
 * @package App\Command\Process
 */
#[AsCommand(name: 'app:process:monitoring', description: 'Main service monitoring process loop')]
class MonitoringProcessCommand extends Command
{
    private AppUtil $appUtil;
    private DatabaseManager $databaseManager;
    private MonitoringManager $monitoringManager;

    public function __construct(AppUtil $appUtil, DatabaseManager $databaseManager, MonitoringManager $monitoringManager)
    {
        $this->appUtil = $appUtil;
        $this->databaseManager = $databaseManager;
        $this->monitoringManager = $monitoringManager;
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

        // fix get CLI ip address
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['HTTP_USER_AGENT'] = 'console';

        // init database down flag
        $databaseDown = false;

        /** @phpstan-ignore-next-line (infinite monitoring loop) */
        while (true) {
            // check if database is down
            $dbState = $this->databaseManager->isDatabaseDown();

            // check database state
            if ($dbState) {
                // print database is down message
                $this->monitoringManager->handleDatabaseDown($io, $databaseDown);

                // set database down flag
                $databaseDown = true;
            } else {
                // init monitroing process
                $this->monitoringManager->monitorInit($io);

                // reset database down flag after resolve database status
                if ($databaseDown) {
                    $databaseDown = false;
                }
            }

            // sleep monitoring interval
            sleep($this->appUtil->getMonitoringInterval() * 60);
        }

        /** @phpstan-ignore-next-line */
        return Command::SUCCESS;
    }
}
