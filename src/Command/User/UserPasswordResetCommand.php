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
     * Configure command and arguments
     *
     * @return void
     */
    protected function configure(): void
    {
        $this->addArgument('username', InputArgument::REQUIRED, 'Username to reset');
    }

    /**
     * Execute user password reset command
     *
     * @param InputInterface $input The input interface
     * @param OutputInterface $output The output interface
     *
     * @throws Exception Error to reset user password
     *
     * @return int The status code
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // fix get CLI visitor info
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['HTTP_USER_AGENT'] = 'console';

        // get input username argument
        $username = $input->getArgument('username');

        // check is username empty
        if (empty($username)) {
            $io->error('Username cannot be empty');
            return Command::FAILURE;
        }

        // check username input type
        if (!is_string($username)) {
            $io->error('Invalid username provided');
            return Command::FAILURE;
        }

        // check if username is used
        if (!$this->userManager->checkIfUserExist($username)) {
            $io->error('Error username: ' . $username . ' does not exist');
            return Command::FAILURE;
        }

        try {
            // reset user password and get them
            $newPassword = $this->authManager->resetUserPassword($username);

            // display success message
            $io->success('User: ' . $username . ' new password is ' . $newPassword);
            return Command::SUCCESS;
        } catch (Exception $e) {
            $io->error('Process error: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
