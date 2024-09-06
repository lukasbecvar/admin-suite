<?php

namespace App\Tests\Command;

use App\Util\ServerUtil;
use PHPUnit\Framework\TestCase;
use App\Manager\DatabaseManager;
use App\Command\RequirementsCheckCommand;
use Symfony\Component\Console\Application;
use PHPUnit\Framework\MockObject\MockObject;
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
    /** @var ServerUtil&MockObject */
    private ServerUtil|MockObject $serverUtil;

    /** @var DatabaseManager&MockObject */
    private DatabaseManager|MockObject $databaseManager;

    /** @var CommandTester */
    private CommandTester $commandTester;

    protected function setUp(): void
    {
        // mock dependencies
        $this->serverUtil = $this->createMock(ServerUtil::class);
        $this->databaseManager = $this->createMock(DatabaseManager::class);

        // create the command
        $command = new RequirementsCheckCommand($this->serverUtil, $this->databaseManager);

        // create an application and add the command
        $application = new Application();
        $application->add($command);

        // create a command tester
        $command = $application->find('app:check:requirements');
        $this->commandTester = new CommandTester($command);
    }

    /**
     * Test the requirements check command
     *
     * @return void
     */
    public function testRequirementsCheckCommand(): void
    {
        // execute the command
        $this->commandTester->execute([]);

        // assert the output
        $this->assertEquals(Command::SUCCESS, $this->commandTester->getStatusCode());
    }
}
