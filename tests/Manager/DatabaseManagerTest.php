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
    private DatabaseManager $databaseManager;
    private AppUtil & MockObject $appUtilMock;
    private LogManager & MockObject $logManagerMock;
    private Connection & MockObject $connectionMock;
    private ErrorManager & MockObject $errorManagerMock;

    protected function setUp(): void
    {
        // mock dependencies
        $this->appUtilMock = $this->createMock(AppUtil::class);
        $this->connectionMock = $this->createMock(Connection::class);
        $this->logManagerMock = $this->createMock(LogManager::class);
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
     * Test the isDatabaseExists method
     *
     * @return void
     */
    public function testIsDatabaseExists(): void
    {
        // check if the database exists
        $output = $this->databaseManager->isDatabaseExists($_ENV['DATABASE_NAME']);

        // assert output is a boolean
        $this->assertIsBool($output);
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

    /**
     * Test the get last page number method
     *
     * @return void
     */
    public function testGetLastPageNumber(): void
    {
        // get the last page number
        $output = $this->databaseManager->getLastPageNumber($_ENV['DATABASE_NAME'], 'users');

        // assert output is an integer
        $this->assertIsInt($output);
    }

    /**
     * Test the get columns list method
     *
     * @return void
     */
    public function testGetColumnsList(): void
    {
        // get the columns list
        $output = $this->databaseManager->getColumnsList($_ENV['DATABASE_NAME'], 'users');

        // assert output is an array
        $this->assertIsArray($output);
    }

    /**
     * Test the doesRecordExist method
     *
     * @return void
     */
    public function testDoesRecordExist(): void
    {
        // check if the record exists
        $output = $this->databaseManager->doesRecordExist($_ENV['DATABASE_NAME'], 'users', 1);

        // assert output is a boolean
        $this->assertIsBool($output);
    }

    /**
     * Test the delete row by id method
     *
     * @return void
     */
    public function testDeleteRowById(): void
    {
        // delete the row
        $output = $this->databaseManager->deleteRowById($_ENV['DATABASE_NAME'], 'users', 2);

        // assert output is a boolean
        $this->assertTrue($output);
    }

    /**
     * Test the getDatabaseDump method
     *
     * @return void
     */
    public function testGetDatabaseDump(): void
    {
        // get the database dump
        $output = $this->databaseManager->getDatabaseDump($_ENV['DATABASE_NAME'], true);

        // assert output is a string
        $this->assertIsString($output);
    }
}
