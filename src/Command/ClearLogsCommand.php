<?php

namespace App\Command;

use Exception;
use App\Entity\Log;
use App\Util\AppUtil;
use App\Manager\DatabaseManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ClearLogsCommand
 *
 * Command for clearing all logs in database
 *
 * @package App\Command
 */
#[AsCommand(name: 'app:log:clear', description: 'Clear all logs in database')]
class ClearLogsCommand extends Command
{
    private AppUtil $appUtil;
    private DatabaseManager $databaseManager;

    public function __construct(AppUtil $appUtil, DatabaseManager $databaseManager)
    {
        $this->appUtil = $appUtil;
        $this->databaseManager = $databaseManager;
        parent::__construct();
    }

    /**
     * Execute command for clearing all logs in database
     *
     * @param InputInterface $input Input interface
     * @param OutputInterface $output Output interface
     *
     * @return int The command status code
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // fix get CLI visitor info
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['HTTP_USER_AGENT'] = 'console';

        // ask for confirmation
        if (!$io->confirm('Are you sure you want to clear all logs?', false)) {
            $io->error('Clearing logs cancelled!');
            return Command::FAILURE;
        }

        try {
            // get database name and table name
            $databaseName = $this->appUtil->getEnvValue('DATABASE_NAME');
            $tableName = $this->databaseManager->getEntityTableName(Log::class);

            // truncate logs table
            $this->databaseManager->tableTruncate($databaseName, $tableName);
            $io->success('Logs cleared successfully!');
            return Command::SUCCESS;
        } catch (Exception $e) {
            $io->error('Error while clearing logs: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
