<?php

namespace App\Tests\Command;

use Exception;
use App\Manager\MetricsManager;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Console\Command\Command;
use App\Command\ValidateServiceVisitorsCommand;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class ValidateServiceVisitorsCommandTest
 *
 * Test cases for ValidateServiceVisitorsCommand
 *
 * @package App\Tests\Command
 */
class ValidateServiceVisitorsCommandTest extends TestCase
{
    private CommandTester $commandTester;
    private MetricsManager & MockObject $metricsManager;

    protected function setUp(): void
    {
        // mock dependencies
        $this->metricsManager = $this->createMock(MetricsManager::class);

        // initialize command instance
        $command = new ValidateServiceVisitorsCommand($this->metricsManager);
        $this->commandTester = new CommandTester($command);
    }

    /**
     * Test execute command success
     *
     * @return void
     */
    public function testExecuteSuccess(): void
    {
        // mock metrics manager
        $this->metricsManager->method('validateServiceVisitors')->willReturn([
            'orphaned_removed' => 5,
            'duplicates_removed' => 10
        ]);

        // execute command
        $exitCode = $this->commandTester->execute([]);

        // get command output
        $output = $this->commandTester->getDisplay();

        // assert result
        $this->assertStringContainsString('Orphaned visitors removed: 5', $output);
        $this->assertStringContainsString('Duplicate visitors removed: 10', $output);
        $this->assertStringContainsString('Service visitors validation completed successfully', $output);
        $this->assertEquals(Command::SUCCESS, $exitCode);
    }

    /**
     * Test execute command with exception
     *
     * @return void
     */
    public function testExecuteThrowsException(): void
    {
        // mock metrics manager
        $this->metricsManager->method('validateServiceVisitors')->willThrowException(new Exception('Test error'));

        // execute command
        $exitCode = $this->commandTester->execute([]);

        // get command output
        $output = $this->commandTester->getDisplay();

        // assert result
        $this->assertStringContainsString('Error during validation: Test error', $output);
        $this->assertEquals(Command::FAILURE, $exitCode);
    }
}
