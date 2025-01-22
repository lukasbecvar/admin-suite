<?php

namespace App\Command\User;

use Exception;
use App\Manager\AuthManager;
use App\Manager\UserManager;
use Symfony\Component\String\ByteString;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class UserRegisterCommand
 *
 * Command to register a new user
 *
 * @package App\Command\User
 */
#[AsCommand(name: 'app:user:register', description: 'Register new user')]
class UserRegisterCommand extends Command
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
     * Configure command arguments
     *
     * @return void
     */
    protected function configure(): void
    {
        $this->addArgument('username', InputArgument::REQUIRED, 'New username');
    }

    /**
     * Execute command to register a new user
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

        // check username input length
        if (strlen($username) < 3 || strlen($username) > 155) {
            $io->error('Username length must be between 3 and 155 characters');
            return Command::FAILURE;
        }

        // check if username is blocked
        if ($this->authManager->isUsernameBlocked($username)) {
            $io->error('Error username: ' . $username . ' is blocked (this username is reserved for system)');
            return Command::FAILURE;
        }

        // check if username is already used
        if ($this->userManager->checkIfUserExist($username)) {
            $io->error('Error username: ' . $username . ' is already used');
            return Command::FAILURE;
        }

        try {
            // generate user password
            $password = ByteString::fromRandom(16)->toString();

            // register user
            $this->authManager->registerUser($username, $password);

            // return success message
            $io->success('New user registered username: ' . $username . ' with password: ' . $password);
            return Command::SUCCESS;
        } catch (Exception $e) {
            $io->error('Process error: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
