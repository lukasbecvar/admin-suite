<?php

namespace App\Tests\Command;

use App\Util\ServerUtil;
use PHPUnit\Framework\TestCase;
use App\Manager\DatabaseManager;
use App\Command\RequirementsCheckCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Class RequirementsCheckCommandTest
 *
 * Test the requirements check command
 *
 * @package App\Tests\Command
 */
class RequirementsCheckCommandTest extends TestCase
{
    /**
     * Test the requirements check command
     *
     * @return void
     */
    public function testRequirementsCheckCommand(): void
    {
        // mock dependencies
        $serverUtil = $this->createMock(ServerUtil::class);
        $databaseManager = $this->createMock(DatabaseManager::class);

        // create the command
        $command = new RequirementsCheckCommand($serverUtil, $databaseManager);

        // create an application and add the command
        $application = new Application();
        $application->add($command);

        // create a command tester
        $commandTester = new CommandTester($command);

        // execute the command
        $commandTester->execute([]);

        // assert the output
        $this->assertEquals(Command::SUCCESS, $commandTester->getStatusCode());
    }
}
