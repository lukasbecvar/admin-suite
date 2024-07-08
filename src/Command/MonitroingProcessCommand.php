<?php

namespace App\Command;

use App\Util\AppUtil;
use App\Manager\MonitroingManager;
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
    private MonitroingManager $monitroingManager;

    public function __construct(AppUtil $appUtil, MonitroingManager $monitroingManager)
    {
        $this->appUtil = $appUtil;
        $this->monitroingManager = $monitroingManager;
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

        // set up signal handling to allow termination via SIGINT (Ctrl+C)
        pcntl_signal(SIGINT, function () use ($io) {
            $io->info('monitoring process terminated.');
            exit(0);
        });

        /** @phpstan-ignore-next-line */
        while (true) {
            // init monitroing process
            $this->monitroingManager->monitorInit($io);

            // sleep monitoring interval
            sleep($this->appUtil->getMonitroingInterval() * 60);
        }

        /** @phpstan-ignore-next-line */
        return Command::SUCCESS;
    }
}
