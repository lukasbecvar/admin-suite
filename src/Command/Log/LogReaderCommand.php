<?php

namespace App\Command\Log;

use App\Manager\LogManager;
use App\Util\VisitorInfoUtil;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class LogReaderCommand
 *
 * Command to get logs by status
 *
 * @package App\Command\Log
 */
#[AsCommand(name: 'app:log:reader', description: 'Get logs by status')]
class LogReaderCommand extends Command
{
    private LogManager $logManager;
    private VisitorInfoUtil $visitorInfoUtil;

    public function __construct(LogManager $logManager, VisitorInfoUtil $visitorInfoUtil)
    {
        $this->logManager = $logManager;
        $this->visitorInfoUtil = $visitorInfoUtil;
        parent::__construct();
    }

    /**
     * Configure command arguments
     *
     * @return void
     */
    protected function configure(): void
    {
        $this->addArgument('status', InputArgument::REQUIRED, 'log status');
    }

    /**
     * Execute command to get logs by status
     *
     * @param InputInterface $input The input interface
     * @param OutputInterface $output The output interface
     *
     * @return int The command exit code
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // set server headers for cli console
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['HTTP_USER_AGENT'] = 'console';

        // get status from input
        $status = $input->getArgument('status');

        // check is status set
        if (empty($status)) {
            $io->error('Status parameter is required');
            return Command::FAILURE;
        }

        // check status type
        if (!is_string($status)) {
            $io->error('Invalid status type provided (must be string)');
            return Command::FAILURE;
        }

        // configure limit content per page for disable pagination limit
        $_ENV['LIMIT_CONTENT_PER_PAGE'] = $this->logManager->getLogsCountWhereStatus() + 100;

        /** @var array<\App\Entity\Log> $logs */
        $logs = $this->logManager->getLogsWhereStatus($status);

        // check if $logs is iterable
        if (!is_iterable($logs)) {
            $io->error('Failed to retrieve logs');
            return Command::FAILURE;
        }

        // build data table
        $data = [];
        foreach ($logs as $log) {
            // get user name of log
            $user = $log->getUser()?->getUsername() ?? 'Unknown user';

            // get log time and format to string
            $time = $log->getTime();
            $fornmatedLoggedDateTime = $time ? $time->format('Y-m-d H:i:s') : 'Unknown';

            // build log data
            $data[] = [
                $log->getId(),
                $log->getName(),
                $log->getMessage(),
                $fornmatedLoggedDateTime,
                $this->visitorInfoUtil->getBrowserShortify($log->getUserAgent() ?? 'Unknown'),
                $this->visitorInfoUtil->getOs($log->getUserAgent() ?? 'Unknown'),
                $log->getIpAddress(),
                $user
            ];
        }

        // return logs table
        $io->table(
            headers: ['#', 'Name', 'Message', 'time', 'Browser', 'OS', 'Ip Address', 'User'],
            rows: $data
        );

        // return success code
        return Command::SUCCESS;
    }
}
