<?php

namespace App\Command\User;

use Exception;
use App\Manager\UserManager;
use App\Manager\AuthManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class UserPasswordResetCommand
 *
 * Command to reset user password
 *
 * @package App\Command\User
 */
#[AsCommand(name: 'app:user:password:reset', description: 'Reset the user password')]
class UserPasswordResetCommand extends Command
{
    private AuthManager $authManager;
    private UserManager $userManager;

    public function __construct(AuthManager $authManager, UserManager $userManager)
    {
        $this->authManager = $authManager;
        $this->userManager = $userManager;
        parent::__construct();
    }

    /**
     * Configure command and arguments
     *
     * @return void
     */
    protected function configure(): void
    {
        $this->addArgument('username', InputArgument::REQUIRED, 'Username of user to reset');
    }

    /**
     * Execute command to reset user password
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

        // get username from input
        $username = $input->getArgument('username');

        // check is username set
        if (empty($username)) {
            $io->error('Username parameter is required');
            return Command::FAILURE;
        }

        // check username input type
        if (!is_string($username)) {
            $io->error('Invalid username type provided (must be string)');
            return Command::FAILURE;
        }

        // check if username is registered
        if (!$this->userManager->checkIfUserExist($username)) {
            $io->error('Error username: ' . $username . ' is not registered');
            return Command::FAILURE;
        }

        try {
            // reset user password and get them
            $newPassword = $this->authManager->resetUserPassword($username);

            // display success message with new password
            $io->success('User: ' . $username . ' new password is ' . $newPassword);
            return Command::SUCCESS;
        } catch (Exception $e) {
            $io->error('Process error: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
