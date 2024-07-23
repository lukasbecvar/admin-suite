<?php

namespace App\Tests\Manager;

use Doctrine\DBAL\Connection;
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
    /** @var DatabaseManager */
    private DatabaseManager $databaseManager;

    /** @var Connection|MockObject */
    private Connection|MockObject $connectionMock;

    protected function setUp(): void
    {
        // create a mock for the Connection class
        $this->connectionMock = $this->createMock(Connection::class);

        // initialize the DatabaseManager with the mock connection
        $this->databaseManager = new DatabaseManager($this->connectionMock);
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
}
