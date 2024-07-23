<?php

namespace App\Tests\Manager;

use App\Util\AppUtil;
use App\Manager\LogManager;
use Doctrine\DBAL\Connection;
use App\Manager\ErrorManager;
use PHPUnit\Framework\TestCase;
use App\Manager\DatabaseManager;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class DatabaseManagerTest
 *
 * Test the database manager
 *
 * @package App\Tests\Manager
 */
class DatabaseManagerTest extends TestCase
{
    /** @var AppUtil|MockObject */
    private AppUtil|MockObject $appUtilMock;

    /** @var DatabaseManager */
    private DatabaseManager $databaseManager;

    /** @var LogManager|MockObject */
    private LogManager|MockObject $logManagerMock;

    /** @var Connection|MockObject */
    private Connection|MockObject $connectionMock;

    /** @var ErrorManager|MockObject */
    private ErrorManager|MockObject $errorManagerMock;

    protected function setUp(): void
    {
        // create a mock for the AppUtil class
        $this->appUtilMock = $this->createMock(AppUtil::class);

        // create a mock for the Connection class
        $this->connectionMock = $this->createMock(Connection::class);

        // create a mock for the LogManager class
        $this->logManagerMock = $this->createMock(LogManager::class);

        // create a mock for the ErrorManager class
        $this->errorManagerMock = $this->createMock(ErrorManager::class);

        // initialize the DatabaseManager with the mock connection
        $this->databaseManager = new DatabaseManager(
            $this->appUtilMock,
            $this->logManagerMock,
            $this->connectionMock,
            $this->errorManagerMock
        );
    }

    /**
     * Test the getDatabaseConnection method with database online
     *
     * @return void
     */
    public function testGetDatabaseConnection(): void
    {
        // assert that getDatabaseConnection returns the mock
        $this->assertSame($this->connectionMock, $this->databaseManager->getDatabaseConnection());
    }

    /**
     * Test the getDatabaseConnection method with database offline
     *
     * @return void
     */
    public function testIsDatabaseDown(): void
    {
        // mock the executeQuery method to throw an exception
        $this->connectionMock->method('executeQuery')->willThrowException(new \Exception());

        // assert that isDatabaseDown returns true
        $this->assertTrue($this->databaseManager->isDatabaseDown());
    }

    /**
     * Test the get databases list method
     *
     * @return void
     */
    public function testGetDatabasesList(): void
    {
        // get the list of databases
        $output = $this->databaseManager->getDatabasesList();

        // assert output is an array
        $this->assertIsArray($output);
    }

    /**
     * Test the get tables list method
     *
     * @return void
     */
    public function testGetTablesList(): void
    {
        // get the list of tables
        $output = $this->databaseManager->getTablesList($_ENV['DATABASE_NAME']);

        // assert output is an array
        $this->assertIsArray($output);
    }

    /**
     * Test the get rows count method
     *
     * @return void
     */
    public function testGetRowsCount(): void
    {
        // get the number of rows in the table
        $output = $this->databaseManager->getTableRowCount($_ENV['DATABASE_NAME'], 'users');

        // assert output is an integer
        $this->assertIsInt($output);
    }

    /**
     * Test the get table data method
     *
     * @return void
     */
    public function testGetTableData(): void
    {
        // get the data from the table
        $output = $this->databaseManager->getTableData($_ENV['DATABASE_NAME'], 'users', 1);

        // assert output is an array
        $this->assertIsArray($output);
    }
}
