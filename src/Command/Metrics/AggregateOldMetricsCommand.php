<?php

namespace App\Command\Metrics;

use DateTime;
use Exception;
use App\Util\AppUtil;
use App\Manager\LogManager;
use App\Manager\MetricsManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class AggregateOldMetricsCommand
 *
 * Command for aggregating old metrics (older than 31 days) into monthly averages
 *
 * @package App\Command\Metrics
 */
#[AsCommand(name: 'app:metrics:aggregate-old', description: 'Aggregate old metrics (older than 31 days) into monthly averages')]
class AggregateOldMetricsCommand extends Command
{
    private AppUtil $appUtil;
    private LogManager $logManager;
    private MetricsManager $metricsManager;

    public function __construct(AppUtil $appUtil, LogManager $logManager, MetricsManager $metricsManager)
    {
        $this->appUtil = $appUtil;
        $this->logManager = $logManager;
        $this->metricsManager = $metricsManager;
        parent::__construct();
    }

    /**
     * Configure command options
     *
     * @return void
     */
    protected function configure(): void
    {
        $this->addOption('days', 'd', InputOption::VALUE_OPTIONAL, 'Number of days to keep detailed metrics (default: 31)', 31);
    }

    /**
     * Execute command for aggregating old metrics
     *
     * @param InputInterface $input Input interface
     * @param OutputInterface $output Output interface
     *
     * @return int The command status code
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // set server headers for cli console
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['HTTP_USER_AGENT'] = 'console';

        // get command arguments
        $daysToKeep = (int) $input->getOption('days');

        // show command info
        $io->title('Metrics Aggregation Tool');
        $io->text('Aggregating metrics older than ' . $daysToKeep . ' days...');

        try {
            // calculate cutoff date
            $cutoffDate = new DateTime('-' . $daysToKeep . ' days');
            $io->text('Cutoff date: ' . $cutoffDate->format('Y-m-d H:i:s'));

            // get aggregation preview
            $preview = $this->metricsManager->getAggregationPreview($cutoffDate);

            if (empty($preview['old_metrics'])) {
                $io->success('No old metrics found to aggregate.');
                return Command::SUCCESS;
            }

            $io->text('Found ' . count($preview['old_metrics']) . ' old metric records to process');
            $io->text('Grouped into ' . count($preview['grouped_metrics']) . ' monthly aggregations');

            // ask for confirmation
            if (!$io->confirm('This will delete ' . count($preview['old_metrics']) . ' detailed records and create ' . count($preview['grouped_metrics']) . ' aggregated records. Continue?', false)) {
                $io->error('Operation cancelled');
                return Command::FAILURE;
            }

            // perform the aggregation
            $result = $this->metricsManager->aggregateOldMetrics($cutoffDate);

            // show result statistics
            $io->success([
                'Metrics aggregation completed successfully!',
                "Restructured metrics table:",
                '- Preserved ' . $result['preserved'] . ' recent detailed records',
                '- Created ' . $result['created'] . ' aggregated monthly records',
                '- Removed ' . $result['deleted'] . ' old detailed records',
                'Saved approximately ' . $this->appUtil->formatBytes($result['space_saved']) . ' of database space'
            ]);

            // log operation
            $this->logManager->log(
                name: 'metrics-aggregation',
                message: 'Aggregated ' . $result['deleted'] . ' old metrics into ' . $result['created'] . ' monthly averages',
                level: LogManager::LEVEL_INFO
            );

            return Command::SUCCESS;
        } catch (Exception $e) {
            $io->error('Error during metrics aggregation: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
