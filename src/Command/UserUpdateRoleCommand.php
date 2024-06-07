<?php

namespace App\Command;

use App\Manager\UserManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class UserUpdateRoleCommand
 *
 * Command to update user role.
 *
 * @package App\Command
 */
#[AsCommand(name: 'app:user:update:role', description: 'Update user role')]
class UserUpdateRoleCommand extends Command
{
    private UserManager $userManager;

    public function __construct(UserManager $userManager)
    {
        $this->userManager = $userManager;
        parent::__construct();
    }

    /**
     * Configure the command.
     *
     * @return void
     */
    protected function configure(): void
    {
        $this->addArgument('username', InputArgument::REQUIRED, 'Username to update role');
        $this->addArgument('role', InputArgument::REQUIRED, 'Role to update');
    }

    /**
     * Execute the command to update user role.
     *
     * @param InputInterface $input The input interface
     * @param OutputInterface $output The output interface
     *
     * @throws \Exception If an error occurs
     *
     * @return int The status code
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // get arguments
        $username = $input->getArgument('username');
        $role = $input->getArgument('role');

        // validate arguments
        if (empty($username)) {
            $io->error('Username cannot be empty.');
            return Command::FAILURE;
        }
        if (empty($role)) {
            $io->error('Role cannot be empty.');
            return Command::FAILURE;
        }
        if (!is_string($role)) {
            $io->error('Invalid role provided.');
            return Command::FAILURE;
        }

        // check if username is used
        if ($this->userManager->getUserRepo(['username' => $username]) == null) {
            $io->error('Error username: ' . $username . ' does not exist.');
            return Command::FAILURE;
        }

        // get user id
        $userId = $this->userManager->getUserRepo(['username' => $username])->getId();

        // check is id is valid
        if ($userId == null) {
            $io->error('Error user id not found.');
            return Command::FAILURE;
        }

        // get current role
        $currentRole = $this->userManager->getUserRole($userId);

        // check if role is the same
        if ($currentRole == $role) {
            $io->error('Error role: ' . $role . ' is already assigned to user: ' . $username);
            return Command::FAILURE;
        }

        // update role
        try {
            // update role
            $this->userManager->updateUserRole($userId, $role);

            // success message
            $io->success('Role updated successfully.');
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Error updating role: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
