<?php

namespace App\Tests\Command\Metrics;

use Exception;
use App\Util\AppUtil;
use App\Entity\Metric;
use App\Manager\LogManager;
use App\Manager\MetricsManager;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use App\Command\Metrics\AggregateOldMetricsCommand;

/**
 * Class AggregateOldMetricsCommandTest
 *
 * Test cases for aggregate old metrics command
 *
 * @package App\Tests\Command\Metrics
 */
class AggregateOldMetricsCommandTest extends TestCase
{
    private CommandTester $commandTester;
    private AppUtil & MockObject $appUtilMock;
    private AggregateOldMetricsCommand $command;
    private LogManager & MockObject $logManagerMock;
    private MetricsManager & MockObject $metricsManagerMock;

    protected function setUp(): void
    {
        // mock dependencies
        $this->appUtilMock = $this->createMock(AppUtil::class);
        $this->logManagerMock = $this->createMock(LogManager::class);
        $this->metricsManagerMock = $this->createMock(MetricsManager::class);

        // initialize the command
        $this->command = new AggregateOldMetricsCommand($this->appUtilMock, $this->logManagerMock, $this->metricsManagerMock);
        $this->commandTester = new CommandTester($this->command);
    }

    /**
     * Test execute command when no old metrics found
     *
     * @return void
     */
    public function testExecuteCommandWhenNoOldMetricsFound(): void
    {
        // mock aggregation preview with no old metrics
        $this->metricsManagerMock->method('getAggregationPreview')->willReturn([
            'old_metrics' => [],
            'recent_metrics' => [],
            'grouped_metrics' => [],
            'space_saved' => 0
        ]);

        // execute the command
        $exitCode = $this->commandTester->execute([]);

        // get command output
        $output = $this->commandTester->getDisplay();

        // assert output contains expected message
        $this->assertStringContainsString('No old metrics found to aggregate', $output);
        $this->assertSame(Command::SUCCESS, $exitCode);
    }

    /**
     * Test execute command when user cancels operation
     *
     * @return void
     */
    public function testExecuteCommandWhenUserCancelsOperation(): void
    {
        // create mock old metrics
        $oldMetric = $this->createMock(Metric::class);
        $oldMetrics = [$oldMetric];

        // mock aggregation preview
        $this->metricsManagerMock->method('getAggregationPreview')->willReturn([
            'old_metrics' => $oldMetrics,
            'recent_metrics' => [],
            'grouped_metrics' => ['group1' => []],
            'space_saved' => 1000
        ]);

        // execute the command with 'no' input (cancel operation)
        $this->commandTester->setInputs(['no']);
        $exitCode = $this->commandTester->execute([]);

        // get command output
        $output = $this->commandTester->getDisplay();

        // assert output contains expected messages
        $this->assertStringContainsString('Found 1 old metric records to process', $output);
        $this->assertStringContainsString('Grouped into 1 monthly aggregations', $output);
        $this->assertStringContainsString('Operation cancelled', $output);
        $this->assertSame(Command::FAILURE, $exitCode);
    }

    /**
     * Test execute command success
     *
     * @return void
     */
    public function testExecuteCommandSuccess(): void
    {
        // create mock old metrics
        $oldMetric = $this->createMock(Metric::class);
        $oldMetrics = [$oldMetric];

        // mock aggregation preview
        $this->metricsManagerMock->method('getAggregationPreview')->willReturn([
            'old_metrics' => $oldMetrics,
            'recent_metrics' => [],
            'grouped_metrics' => ['group1' => []],
            'space_saved' => 1000
        ]);

        // mock aggregation result
        $this->metricsManagerMock->method('aggregateOldMetrics')->willReturn([
            'deleted' => 1,
            'created' => 1,
            'preserved' => 0,
            'space_saved' => 1000
        ]);

        // mock format bytes
        $this->appUtilMock->method('formatBytes')->with(1000)->willReturn('1000 B');

        // expect log call
        $this->logManagerMock->expects($this->once())->method('log')->with(
            'metrics-aggregation',
            'Aggregated 1 old metrics into 1 monthly averages',
            LogManager::LEVEL_INFO
        );

        // execute the command with 'yes' input (confirm operation)
        $this->commandTester->setInputs(['yes']);
        $exitCode = $this->commandTester->execute([]);

        // get command output
        $output = $this->commandTester->getDisplay();

        // assert output contains expected messages
        $this->assertStringContainsString('Found 1 old metric records to process', $output);
        $this->assertStringContainsString('Grouped into 1 monthly aggregations', $output);
        $this->assertStringContainsString('Metrics aggregation completed successfully!', $output);
        $this->assertStringContainsString('Preserved 0 recent detailed records', $output);
        $this->assertStringContainsString('Created 1 aggregated monthly records', $output);
        $this->assertStringContainsString('Removed 1 old detailed records', $output);
        $this->assertStringContainsString('Saved approximately 1000 B of database space', $output);
        $this->assertSame(Command::SUCCESS, $exitCode);
    }

    /**
     * Test execute command with custom days option
     *
     * @return void
     */
    public function testExecuteCommandWithCustomDaysOption(): void
    {
        // mock aggregation preview with no old metrics
        $this->metricsManagerMock->method('getAggregationPreview')->willReturn([
            'old_metrics' => [],
            'recent_metrics' => [],
            'grouped_metrics' => [],
            'space_saved' => 0
        ]);

        // execute the command with custom days option
        $exitCode = $this->commandTester->execute(['--days' => 60]);

        // get command output
        $output = $this->commandTester->getDisplay();

        // assert output contains custom days message
        $this->assertStringContainsString('Aggregating metrics older than 60 days', $output);
        $this->assertSame(Command::SUCCESS, $exitCode);
    }

    /**
     * Test execute command when exception occurs
     *
     * @return void
     */
    public function testExecuteCommandWhenExceptionOccurs(): void
    {
        // mock exception during aggregation preview
        $this->metricsManagerMock->method('getAggregationPreview')->willThrowException(new Exception('Database connection error'));

        // execute the command
        $exitCode = $this->commandTester->execute([]);

        // get command output
        $output = $this->commandTester->getDisplay();

        // assert output contains error message
        $this->assertStringContainsString('Error during metrics aggregation: Database connection error', $output);
        $this->assertSame(Command::FAILURE, $exitCode);
    }

    /**
     * Test execute command with force option
     *
     * @return void
     */
    public function testExecuteCommandWithForceOption(): void
    {
        // create mock old metrics
        $oldMetric = $this->createMock(Metric::class);
        $oldMetrics = [$oldMetric];

        // mock aggregation preview
        $this->metricsManagerMock->method('getAggregationPreview')->willReturn([
            'old_metrics' => $oldMetrics,
            'recent_metrics' => [],
            'grouped_metrics' => ['group1' => []],
            'space_saved' => 1000
        ]);

        // mock aggregation result
        $this->metricsManagerMock->method('aggregateOldMetrics')->willReturn([
            'deleted' => 1,
            'created' => 1,
            'preserved' => 0,
            'space_saved' => 1000
        ]);

        // mock format bytes
        $this->appUtilMock->method('formatBytes')->with(1000)->willReturn('1000 B');

        // expect log call
        $this->logManagerMock->expects($this->once())->method('log')->with(
            'metrics-aggregation',
            'Aggregated 1 old metrics into 1 monthly averages',
            LogManager::LEVEL_INFO
        );

        // execute the command with --force option (should skip confirmation)
        $exitCode = $this->commandTester->execute(['--force' => true]);

        // get command output
        $output = $this->commandTester->getDisplay();

        // assert output contains expected messages
        $this->assertStringContainsString('Running in FORCE mode - skipping confirmation prompt', $output);
        $this->assertStringContainsString('Force mode enabled - proceeding without confirmation', $output);
        $this->assertStringContainsString('Metrics aggregation completed successfully!', $output);
        $this->assertStringNotContainsString('Continue?', $output); // should not show confirmation prompt
        $this->assertSame(Command::SUCCESS, $exitCode);
    }

    /**
     * Test execute command with force option and custom days
     *
     * @return void
     */
    public function testExecuteCommandWithForceOptionAndCustomDays(): void
    {
        // mock aggregation preview with no old metrics
        $this->metricsManagerMock->method('getAggregationPreview')->willReturn([
            'old_metrics' => [],
            'recent_metrics' => [],
            'grouped_metrics' => [],
            'space_saved' => 0
        ]);

        // execute the command with both --force and --days options
        $exitCode = $this->commandTester->execute(['--force' => true, '--days' => 60]);

        // get command output
        $output = $this->commandTester->getDisplay();

        // assert output contains both force mode and custom days messages
        $this->assertStringContainsString('Running in FORCE mode - skipping confirmation prompt', $output);
        $this->assertStringContainsString('Aggregating metrics older than 60 days', $output);
        $this->assertStringContainsString('No old metrics found to aggregate', $output);
        $this->assertSame(Command::SUCCESS, $exitCode);
    }
}
