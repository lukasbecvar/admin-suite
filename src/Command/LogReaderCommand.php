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
#[AsCommand(name: 'app:log:reader', description: 'get logs by status')]
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
     * Configures the command arguments
     *
     * @return void
     */
    protected function configure(): void
    {
        $this->addArgument('status', InputArgument::REQUIRED, 'log status');
    }

    /**
     * Executes the command to get logs by status
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

        // get input arguments
        $status = $input->getArgument('status');

        // check if status is empty
        if (empty($status)) {
            $io->error('status cannot be empty.');
            return Command::FAILURE;
        }

        // check status type
        if (!is_string($status)) {
            $io->error('Invalid status provided.');
            return Command::FAILURE;
        }

        // set limit content per page to get all logs
        $_ENV['LIMIT_CONTENT_PER_PAGE'] = $this->logManager->getLogsCountWhereStatus() + 100;

        /** @var \App\Entity\Log $logs */
        $logs = $this->logManager->getLogsWhereStatus($status);

        // check if $logs is iterable
        if (!is_iterable($logs)) {
            $io->error('Failed to retrieve logs.');
            return Command::FAILURE;
        }

        // build data for table
        $data = [];
        foreach ($logs as $log) {
            // get user name
            $user = $this->userManager->getUsernameById($log->getUserId()) ?? 'Unknown user';

            // build log data
            $data[] = [
                $log->getId(),
                $log->getName(),
                $log->getMessage(),
                $log->getTime()->format('Y-m-d H:i:s'),
                $this->visitorInfoUtil->getBrowserShortify($log->getUserAgent()),
                $this->visitorInfoUtil->getOs($log->getUserAgent()),
                $log->getIpAdderss(),
                $user
            ];
        }

        // render the table
        $io->table(
            ['#', 'Name', 'Message', 'time', 'Browser', 'OS', 'Ip Address', 'User'],
            $data
        );

        // return success code
        return Command::SUCCESS;
    }
}
