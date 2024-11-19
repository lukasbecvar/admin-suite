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
 * Class UserDeleteCommand
 *
 * Command to delete a user by username
 *
 * @package App\Command\User
 */
#[AsCommand(name: 'app:user:delete', description: 'Delete user from database')]
class UserDeleteCommand extends Command
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
        $this->addArgument('username', InputArgument::REQUIRED, 'Username to delete');
    }

    /**
     * Execute command to delete user by username
     *
     * @param InputInterface $input The input interface
     * @param OutputInterface $output The output interface
     *
     * @throws \Exception If an error occurs while executing the command
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

        // check if username is empty
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
            $io->error('Error username: ' . $username . ' not exist');
            return Command::FAILURE;
        }

        /** @var \App\Entity\User $userRepository */
        $userRepository = $this->userManager->getUserRepository(['username' => $username]);

        // get user id
        $userId = (int) $userRepository->getId();

        // delete user process
        try {
            $this->userManager->deleteUser($userId);
            $io->success('User: ' . $username . ' has been deleted');
            return Command::SUCCESS;
        } catch (Exception $e) {
            $io->error('Process error: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
