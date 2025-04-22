<?php

namespace App\Command;

use Exception;
use App\Util\AppUtil;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ToggleMaintenanceCommand
 *
 * Command to enable/disable maintenance mode
 *
 * @package App\Command
 */
#[AsCommand(name: 'app:toggle:maintenance', description: 'Enable/disable maintenance mode')]
class ToggleMaintenanceCommand extends Command
{
    private AppUtil $appUtil;

    public function __construct(AppUtil $appUtil)
    {
        $this->appUtil = $appUtil;
        parent::__construct();
    }

    /**
     * Configure command arguments
     *
     * @return void
     */
    protected function configure(): void
    {
        $this->addArgument('mode', InputArgument::REQUIRED, 'new maintenance mode mode');
    }

    /**
     * Execute maintenance mode toggle command
     *
     * @param InputInterface $input The input interface
     * @param OutputInterface $output The output interface
     *
     * @return int The command exit code
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // get mode from input
        $mode = $input->getArgument('mode');

        // check is mode set
        if (empty($mode)) {
            $io->error('mode parameter is required');
            return Command::FAILURE;
        }

        // check mode type
        if (!is_string($mode)) {
            $io->error('Invalid mode type provided (must be string)');
            return Command::FAILURE;
        }

        // check if new mode is valid
        if (!in_array($mode, ['true', 'false'])) {
            $io->error('Invalid mode provided (must be true or false)');
            return Command::FAILURE;
        }

        try {
            // update env value
            $this->appUtil->updateEnvValue('MAINTENANCE_MODE', $mode);

            // return success status
            $io->success('MAINTENANCE_MODE in .env has been set to: ' . $mode);
            return Command::SUCCESS;
        } catch (Exception $e) {
            $io->error('Process error: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
