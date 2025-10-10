<?php

namespace App\Command;

use Exception;
use App\Util\AppUtil;
use App\Manager\TodoManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class RotateAppSecretCommand
 *
 * Command to rotate APP_SECRET key value in environment configuration
 *
 * @package App\Command
 */
#[AsCommand(name: 'app:rotate:app-secret', description: 'Rotate the APP_SECRET key value in environment configuration')]
class RotateAppSecretCommand extends Command
{
    private AppUtil $appUtil;
    private TodoManager $todoManager;

    public function __construct(AppUtil $appUtil, TodoManager $todoManager)
    {
        $this->appUtil = $appUtil;
        $this->todoManager = $todoManager;
        parent::__construct();
    }

    /**
     * Execute rotate APP_SECRET command
     *
     * @param InputInterface $input The input interface
     * @param OutputInterface $output The output interface
     *
     * @return int The command exit code
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            // get old APP_SECRET key
            $oldSecret = $this->appUtil->getEnvValue('APP_SECRET');

            // generate new APP_SECRET key
            $newSecret = $this->appUtil->generateKey(16);

            // update value in environment configuration
            $this->appUtil->updateEnvValue('APP_SECRET', $newSecret);

            // re-encrypt todos
            $this->todoManager->reEncryptTodos($oldSecret, $newSecret);

            // success output
            $io->success('APP_SECRET has been rotated successfully!');
            $io->note('Remember: Sessions, remember-me tokens, and encrypted data may become invalid.');
            return Command::SUCCESS;
        } catch (Exception $e) {
            $io->error('Error during rotation: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
