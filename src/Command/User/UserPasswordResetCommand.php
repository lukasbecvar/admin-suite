<?php

namespace App\Command\User;

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
 * Command to reset the user password
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
     * Configures the command and arguments
     *
     * @return void
     */
    protected function configure(): void
    {
        $this->addArgument('username', InputArgument::REQUIRED, 'Username to reset');
    }

    /**
     * Executes the user password reset command
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
        $username = $input->getArgument('username');

        // check if username is empty
        if (empty($username)) {
            $io->error('Username cannot be empty.');
            return Command::FAILURE;
        }

        // check username type
        if (!is_string($username)) {
            $io->error('Invalid username provided.');
            return Command::FAILURE;
        }

        // check if username is used
        if (!$this->userManager->checkIfUserExist($username)) {
            $io->error('Error username: ' . $username . ' does not exist!');
            return Command::FAILURE;
        }

        // reset user password
        try {
            $newPassword = $this->authManager->resetUserPassword($username);

            // display success message
            $io->success('User: ' . $username . ' new password is ' . $newPassword);
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Process error: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
