<?php

namespace App\Command;

use Exception;
use App\Manager\UserManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class GrantAdminRoleCommand
 *
 * Command to grant admin role to a user.
 *
 * @package App\Command
 */
#[AsCommand(name: 'app:user:role:grant', description: 'Grant admin role to user')]
class GrantAdminRoleCommand extends Command
{
    private UserManager $userManager;

    /**
     * GrantAdminRoleCommand constructor
     *
     * @param UserManager $userManager The user manager instance.
     */
    public function __construct(UserManager $userManager)
    {
        $this->userManager = $userManager;
        parent::__construct();
    }

    /**
     * Configures the command arguments.
     */
    protected function configure(): void
    {
        $this->addArgument('username', InputArgument::REQUIRED, 'New admin user username');
    }

    /**
     * Executes the command to grant admin role to a user.
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

        // get username argument
        $username = $input->getArgument('username');

        // check if username is added
        if ($username == null) {
            $io->error('You must add the admin username argument!');
            return Command::FAILURE;
        }

        // check if username are string
        if (!is_string($username)) {
            $io->error('Invalid username provided.');
            return Command::FAILURE;
        }

        // check if username is used
        if ($this->userManager->getUserRepo(['username' => $username]) == null) {
            $io->error('Error username: ' . $username . ' is not registered!');
            return Command::FAILURE;
        }

        // convert username to string
        $username = strval($username);

        try {
            // check if user is admin
            if ($this->userManager->isUserAdmin($username)) {
                $io->error('User: ' . $username . ' is already admin');
                return Command::FAILURE;
            } else {
                // grant role to user
                $this->userManager->addAdminRoleToUser($username);

                // return success message
                $io->success('admin role granted to: ' . $username);
                return Command::SUCCESS;
            }
        } catch (Exception $e) {
            $io->success('error to grant admin: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
