<?php

namespace App\Command;

use App\Manager\AuthManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class RegenerateAuthTokensCommand
 *
 * Command to regenerate all users authentication tokens in the database
 *
 * @package App\Command
 */
#[AsCommand(name: 'app:auth:tokens:regenerate', description: 'Regenerate all users tokens in database')]
class RegenerateAuthTokensCommand extends Command
{
    private AuthManager $authManager;

    public function __construct(AuthManager $authManager)
    {
        $this->authManager = $authManager;
        parent::__construct();
    }

    /**
     * Execute command to regenerate all users authentication tokens
     *
     * @param InputInterface $input The input interface
     * @param OutputInterface $output The output interface
     *
     * @return int The status code
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // fix get CLI visitor info
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['HTTP_USER_AGENT'] = 'console';

        // regenerate all tokens and get status
        $regenerateState = $this->authManager->regenerateUsersTokens();

        // check if regeneration is success
        if ($regenerateState['status']) {
            $io->success('All tokens is regenerated');
            return Command::SUCCESS;
        } else {
            $io->error('Token regeneration error: ' . $regenerateState['message']);
            return Command::FAILURE;
        }
    }
}
