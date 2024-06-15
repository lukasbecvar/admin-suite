<?php

namespace App\Command\User;

use App\Manager\BanManager;
use App\Manager\UserManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class UserBanCommand
 *
 * Command to ban or unban a user by username
 *
 * @package App\Command
 */
#[AsCommand(name: 'app:user:ban', description: 'Ban user')]
class UserBanCommand extends Command
{
    private BanManager $banManager;
    private UserManager $userManager;

    public function __construct(BanManager $banManager, UserManager $userManager)
    {
        $this->banManager = $banManager;
        $this->userManager = $userManager;
        parent::__construct();
    }

    /**
     * Configures the current command
     *
     * @return void
     */
    protected function configure(): void
    {
        $this->addArgument('username', InputArgument::REQUIRED, 'Username to ban');
    }

    /**
     * Executes the command to ban user by username
     *
     * @param InputInterface $input The input interface
     * @param OutputInterface $output The output interface
     *
     * @return int The status code
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

        // check if username are string
        if (!is_string($username)) {
            $io->error('Invalid username provided.');
            return Command::FAILURE;
        }

        // check if username is used
        if (!$this->userManager->checkIfUserExist($username)) {
            $io->error('Error username: ' . $username . ' not exist!');
            return Command::FAILURE;
        }

        /** @var \App\Entity\User $userRepository */
        $userRepository = $this->userManager->getUserRepository(['username' => $username]);

        // get user id
        $userId = (int) $userRepository->getId();

        try {
            // check if user is banned
            if ($this->banManager->isUserBanned($userId)) {
                $this->banManager->unbanUser($userId);
                $io->success('User: ' . $username . ' unbanned.');
            } else {
                $this->banManager->banUser($userId);
                $io->success('User: ' . $username . ' banned.');
            }
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Process error: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
