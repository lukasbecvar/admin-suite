<?php

namespace App\Command\User;

use Exception;
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
 * Command to ban/unban user by username
 *
 * @package App\Command
 */
#[AsCommand(name: 'app:user:ban', description: 'Ban/unban the user')]
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
     * Configure command arguments
     *
     * @return void
     */
    protected function configure(): void
    {
        $this->addArgument('username', InputArgument::REQUIRED, 'Username to ban');
    }

    /**
     * Execute command to ban/unban user by username
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

        // check if username is empty
        if (empty($username)) {
            $io->error('Username parameter is required');
            return Command::FAILURE;
        }

        // check username input type
        if (!is_string($username)) {
            $io->error('Invalid username type provided (must be string)');
            return Command::FAILURE;
        }

        // check if user found in database
        if (!$this->userManager->checkIfUserExist($username)) {
            $io->error('Error username: ' . $username . ' not exist');
            return Command::FAILURE;
        }

        /** @var \App\Entity\User $userRepository */
        $userRepository = $this->userManager->getUserRepository(['username' => $username]);

        // get user id
        $userId = (int) $userRepository->getId();

        try {
            // check if user is already banned
            if ($this->banManager->isUserBanned($userId)) {
                // unban user
                $this->banManager->unBanUser($userId);
                $io->success('User: ' . $username . ' is unbanned successfully');
            } else {
                // ban user
                $this->banManager->banUser($userId);
                $io->success('User: ' . $username . ' is banned successfully');
            }
            return Command::SUCCESS;
        } catch (Exception $e) {
            $io->error('Process error: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
