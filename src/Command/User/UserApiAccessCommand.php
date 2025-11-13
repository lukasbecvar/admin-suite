<?php

namespace App\Command\User;

use Exception;
use App\Manager\UserManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class UserApiAccessCommand
 *
 * Command to enable or disable API access
 *
 * @package App\Command\User
 */
#[AsCommand(name: 'app:user:api-access', description: 'Enable or disable API access for a user')]
class UserApiAccessCommand extends Command
{
    private UserManager $userManager;

    public function __construct(UserManager $userManager)
    {
        $this->userManager = $userManager;
        parent::__construct();
    }

    /**
     * Configure command arguments
     *
     * @return void
     */
    protected function configure(): void
    {
        $this
            ->addArgument('username', InputArgument::REQUIRED, 'Username to update')
            ->addArgument('status', InputArgument::REQUIRED, 'API access status (enable|disable)');
    }

    /**
     * Execute command to update API access status
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

        // get command arguments
        $username = $input->getArgument('username');
        $status = $input->getArgument('status');

        // validate username
        if (empty($username) || !is_string($username)) {
            $io->error('Invalid username parameter (must be non-empty string)');
            return Command::FAILURE;
        }

        // validate status
        if (empty($status) || !is_string($status)) {
            $io->error('Invalid status parameter (must be non-empty string)');
            return Command::FAILURE;
        }
        $normalizedStatus = strtolower($status);
        if (!in_array($normalizedStatus, ['enable', 'disable'], true)) {
            $io->error('Invalid status value provided. Use "enable" or "disable".');
            return Command::FAILURE;
        }

        // get user entity
        $user = $this->userManager->getUserByUsername($username);

        if ($user === null || $user->getId() === null) {
            $io->error('User "' . $username . '" was not found.');
            return Command::FAILURE;
        }

        $allowAccess = $normalizedStatus === 'enable';

        // check if change is necessary
        if ((bool) $user->getAllowApiAccess() === $allowAccess) {
            $state = $allowAccess ? 'enabled' : 'disabled';
            $io->warning('API access is already ' . $state . ' for user: ' . $username);
            return Command::SUCCESS;
        }

        try {
            $this->userManager->updateApiAccessStatus(
                userId: (int) $user->getId(),
                allowApiAccess: $allowAccess,
                source: 'user-manager'
            );
            $io->success('API access has been ' . ($allowAccess ? 'enabled' : 'disabled') . ' for user: ' . $username);
            return Command::SUCCESS;
        } catch (Exception $e) {
            $io->error('Process error: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
