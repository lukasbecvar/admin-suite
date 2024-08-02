<?php

namespace App\Command;

use App\Util\ServerUtil;
use App\Manager\DatabaseManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class RequirementsCheckCommand
 *
 * Command to check app requirements and configuration
 *
 * @package App\Command
 */
#[AsCommand(name: 'app:check:requirements', description: 'check app requirements and configuration')]
class RequirementsCheckCommand extends Command
{
    private ServerUtil $serverUtil;
    private DatabaseManager $databaseManager;

    public function __construct(ServerUtil $serverUtil, DatabaseManager $databaseManager)
    {
        $this->serverUtil = $serverUtil;
        $this->databaseManager = $databaseManager;
        parent::__construct();
    }

    /**
     * Executes the command to check app requirements and configuration
     *
     * @param InputInterface $input The input interface
     * @param OutputInterface $output The output interface
     *
     * @return int The exit code of the command
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // get not installed requirements
        $notInstalledRequirements = $this->serverUtil->getNotInstalledRequirements();

        // check if all requirements are installed
        if (empty($notInstalledRequirements)) {
            $io->success('All requirements are installed.');
        } else {
            $io->error('The following requirements are not installed: ' . implode(', ', $notInstalledRequirements));
        }

        // exception files config file exist check
        if (file_exists(__DIR__ . '/../../exception-files.json')) {
            $io->success('exception files config file found in /exception-files.json');
        } elseif (file_exists(__DIR__ . '/../../config/suite/exception-files.json')) {
            $io->success('exception files config file found in /config/suite/exception-files.json');
        } else {
            $io->error('exception-files.json config file not found');
        }

        // package requirements config file exist check
        if (file_exists(__DIR__ . '/../../package-requirements.json')) {
            $io->success('requirements config file found in /package-requirements.json');
        } elseif (file_exists(__DIR__ . '/../../config/suite/package-requirements.json')) {
            $io->success('requirements config file found in /config/suite/package-requirements.json');
        } else {
            $io->error('package-requirements.json config file not found');
        }

        // services config file exist check
        if (file_exists(__DIR__ . '/../../services.json')) {
            $io->success('services config file found in /services.json');
        } elseif (file_exists(__DIR__ . '/../../config/suite/services.json')) {
            $io->success('services config file found in /config/suite/services.json');
        } else {
            $io->error('services.json config file not found');
        }

        // check database connection
        if (!$this->databaseManager->isDatabaseDown()) {
            $io->success('Database connected successfully');
        } else {
            $io->error('Database connection failed');
        }

        return Command::SUCCESS;
    }
}
