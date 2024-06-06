<?php

namespace App\Command;

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
 * Command to register a new user.
 *
 * @package App\Command
 */
#[AsCommand(name: 'app:user:register', description: 'Register new user')]
class UserRegisterCommand extends Command
{
    private UserManager $userManager;

    /**
     * UserRegisterCommand constructor
     *
     * @param UserManager $userManager The user manager instance.
     */
    public function __construct(UserManager $userManager)
    {
        $this->userManager = $userManager;
        parent::__construct();
    }

    /**
     * Configures the current command.
     *
     * This method is responsible for configuring the command by defining its name, description, and any arguments or options it accepts.
     *
     * @return void
     */
    protected function configure(): void
    {
        $this->addArgument('username', InputArgument::REQUIRED, 'New user name');
    }

    /**
     * Executes the command to register a new user.
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

        // check username length
        if (strlen($username) < 3 || strlen($username) > 155) {
            $io->error('Username must be between 3 and 155 characters.');
            return Command::FAILURE;
        }

        // check if username is used
        if ($this->userManager->getUserRepo(['username' => $username]) != null) {
            $io->error('Error username: ' . $username . ' is already used!');
            return Command::FAILURE;
        }

        try {
            // generate user password
            $password = ByteString::fromRandom(32)->toString();

            // register user
            $this->userManager->registerUser(strval($username), $password);

            // return success message
            $io->success('New user registered username: ' . $username . ' with password: ' . $password);
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->success('error to register user: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
