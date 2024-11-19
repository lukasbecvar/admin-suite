<?php

namespace App\Command\User;

use App\Manager\UserManager;
use App\Util\VisitorInfoUtil;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class UserListCommand
 *
 * Command to list all users in database
 *
 * @package App\Command\User
 */
#[AsCommand(name: 'app:user:list', description: 'List all users in database')]
class UserListCommand extends Command
{
    private UserManager $userManager;
    private VisitorInfoUtil $visitorInfoUtil;

    public function __construct(UserManager $userManager, VisitorInfoUtil $visitorInfoUtil)
    {
        $this->userManager = $userManager;
        $this->visitorInfoUtil = $visitorInfoUtil;
        parent::__construct();
    }

    /**
     * Execute command to list all users in database
     *
     * @param InputInterface $input The input interface
     * @param OutputInterface $output The output interface
     *
     * @return int The status code
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        /** @var \App\Entity\User $users */
        $users = $this->userManager->getAllUsersRepositories();

        // check if user list is empty
        if ($this->userManager->isUsersEmpty()) {
            $io->success('User list is empty');
            return Command::SUCCESS;
        }

        // check is $users iterable
        if (!is_iterable($users)) {
            $io->error('Failed to retrieve users');
            return Command::FAILURE;
        }

        // build data for table
        $data = [];
        foreach ($users as $user) {
            $data[] = [
                $user->getId(),
                $user->getUsername(),
                $user->getRole(),
                $user->getIpAddress(),
                $this->visitorInfoUtil->getBrowserShortify($user->getUserAgent()),
                $this->visitorInfoUtil->getOs($user->getUserAgent()),
                $user->getRegisterTime()->format('Y-m-d H:i:s'),
                $user->getLastLoginTime()->format('Y-m-d H:i:s')
            ];
        }

        // render user list table
        $io->table(
            headers: ['#', 'Username', 'Role', 'Ip address', 'Browser', 'OS', 'Register time', 'Last login'],
            rows: $data
        );

        // return success code
        return Command::SUCCESS;
    }
}
