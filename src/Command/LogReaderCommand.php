<?php

namespace App\Command;

use App\Manager\LogManager;
use App\Manager\UserManager;
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
 * @package App\Command
 */
#[AsCommand(name: 'app:log:reader', description: 'Get logs by status')]
class LogReaderCommand extends Command
{
    private LogManager $logManager;
    private UserManager $userManager;
    private VisitorInfoUtil $visitorInfoUtil;

    public function __construct(LogManager $logManager, UserManager $userManager, VisitorInfoUtil $visitorInfoUtil)
    {
        $this->logManager = $logManager;
        $this->userManager = $userManager;
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
     * @return int The status code
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // fix get CLI visitor info
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['HTTP_USER_AGENT'] = 'console';

        // get input status argument
        $status = $input->getArgument('status');

        // check if status is empty
        if (empty($status)) {
            $io->error('status cannot be empty');
            return Command::FAILURE;
        }

        // check status input type
        if (!is_string($status)) {
            $io->error('Invalid status provided');
            return Command::FAILURE;
        }

        // set limit content per page to get all logs
        $_ENV['LIMIT_CONTENT_PER_PAGE'] = $this->logManager->getLogsCountWhereStatus() + 100;

        /** @var array<\App\Entity\Log> $logs */
        $logs = $this->logManager->getLogsWhereStatus($status);

        // check if $logs is iterable
        if (!is_iterable($logs)) {
            $io->error('Failed to retrieve logs');
            return Command::FAILURE;
        }

        // build data for table
        $data = [];
        foreach ($logs as $log) {
            // get user name
            $user = $this->userManager->getUsernameById($log->getUserId() ?? 0) ?? 'Unknown user';

            // get log time
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

        // render logs table
        $io->table(
            headers: ['#', 'Name', 'Message', 'time', 'Browser', 'OS', 'Ip Address', 'User'],
            rows: $data
        );

        // return success code
        return Command::SUCCESS;
    }
}
