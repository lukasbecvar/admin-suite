<?php

namespace App\Command;

use Exception;
use App\Manager\MetricsManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class ValidateServiceVisitorsCommand
 *
 * Command to validate service visitors data
 *
 * @package App\Command
 */
#[AsCommand(name: 'app:metrics:visitors:validate', description: 'Validate service visitors data')]
class ValidateServiceVisitorsCommand extends Command
{
    private MetricsManager $metricsManager;

    public function __construct(MetricsManager $metricsManager)
    {
        $this->metricsManager = $metricsManager;
        parent::__construct();
    }

    /**
     * Execute validation command
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

        try {
            $io->title('Starting service visitors validation...');

            // validate visitors
            $result = $this->metricsManager->validateServiceVisitors();

            // show validation results
            $io->success('Orphaned visitors removed: ' . $result['orphaned_removed']);
            $io->success('Duplicate visitors removed: ' . $result['duplicates_removed']);
            $io->success('Table re-indexing was forced');

            // print success message
            $io->success('Service visitors validation completed successfully');
            return Command::SUCCESS;
        } catch (Exception $e) {
            $io->error('Error during validation: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
