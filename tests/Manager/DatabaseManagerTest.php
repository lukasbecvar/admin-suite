<?php

namespace App\Tests\Manager;

use Exception;
use App\Util\AppUtil;
use Doctrine\DBAL\Result;
use App\Manager\LogManager;
use Doctrine\DBAL\Connection;
use App\Manager\ErrorManager;
use PHPUnit\Framework\TestCase;
use App\Manager\DatabaseManager;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class DatabaseManagerTest
 *
 * Test cases for database manager
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

        // initialize the database manager instance
        $this->databaseManager = new DatabaseManager(
            $this->appUtilMock,
            $this->logManagerMock,
            $this->connectionMock,
            $this->errorManagerMock
        );
    }

    /**
     * Test get database connection
     *
     * @return void
     */
    public function testGetDatabaseConnection(): void
    {
        // call the method
        $connection = $this->databaseManager->getDatabaseConnection();

        // assert result
        $this->assertSame($this->connectionMock, $connection);
    }

    /**
     * Test check if database is down
     *
     * @return void
     */
    public function testIsDatabaseDown(): void
    {
        // mock executeQuery exception return
        $this->connectionMock->method('executeQuery')->willThrowException(new Exception());

        // call the method
        $result = $this->databaseManager->isDatabaseDown();

        // assert result
        $this->assertTrue($result);
    }

    /**
     * Test get the list of databases
     *
     * @return void
     */
    public function testGetDatabasesList(): void
    {
        // expect log manager call
        $this->logManagerMock->expects($this->once())->method('log')->with(
            $this->equalTo('database-manager'),
            $this->equalTo('get databases list'),
            $this->equalTo(LogManager::LEVEL_NOTICE)
        );

        // expect handleError not be called
        $this->errorManagerMock->expects($this->never())->method('handleError');

        // call tested method
        $result = $this->databaseManager->getDatabasesList();

        // assert result
        $this->assertIsArray($result);
    }

    /**
     * Test check if a database exists
     *
     * @return void
     */
    public function testIsDatabaseExists(): void
    {
        $dbName = 'test_db';

        // expect executeQuery call
        $this->connectionMock->expects($this->once())->method('executeQuery')
            ->with('SHOW DATABASES LIKE :dbName', ['dbName' => $dbName]);

        // expect handleError not be called
        $this->errorManagerMock->expects($this->never())->method('handleError');

        // call tested method
        $result = $this->databaseManager->isDatabaseExists($dbName);

        // assert result
        $this->assertIsBool($result);
    }

    /**
     * Test get tables list
     *
     * @return void
     */
    public function testGetTablesList(): void
    {
        // expect log manager call
        $this->logManagerMock->expects($this->once())->method('log')->with(
            $this->equalTo('database-manager'),
            $this->equalTo('get tables list'),
            $this->equalTo(LogManager::LEVEL_NOTICE)
        );

        // expect handleError not be called
        $this->errorManagerMock->expects($this->never())->method('handleError');

        // call tested method
        $result = $this->databaseManager->getTablesList($_ENV['DATABASE_NAME']);

        // assert result
        $this->assertIsArray($result);
    }

    /**
     * Test check if a table exists
     *
     * @return void
     */
    public function testIsTableExists(): void
    {
        // expect executeQuery call
        $this->connectionMock->expects($this->once())->method('executeQuery');

        // expect handleError not be called
        $this->errorManagerMock->expects($this->never())->method('handleError');

        // call tested method
        $result = $this->databaseManager->isTableExists($_ENV['DATABASE_NAME'], 'users');

        // assert result
        $this->assertIsBool($result);
    }

    /**
     * Test get table rows count
     *
     * @return void
     */
    public function testGetTableRowsCount(): void
    {
        // expect executeQuery call
        $this->connectionMock->expects($this->once())->method('executeQuery');

        // expect handleError not be called
        $this->errorManagerMock->expects($this->never())->method('handleError');

        // call tested method
        $result = $this->databaseManager->getTableRowCount($_ENV['DATABASE_NAME'], 'users');

        // assert result
        $this->assertIsInt($result);
    }

    /**
     * Test get table data
     *
     * @return void
     */
    public function testGetTableData(): void
    {
        // call tested method
        $result = $this->databaseManager->getTableData($_ENV['DATABASE_NAME'], 'users', 1);

        // assert result
        $this->assertIsArray($result);
    }

    /**
     * Test get columns list
     *
     * @return void
     */
    public function testGetColumnsList(): void
    {
        // expect executeQuery call
        $this->connectionMock->expects($this->once())->method('executeQuery');

        // expect handleError not be called
        $this->errorManagerMock->expects($this->never())->method('handleError');

        // call tested method
        $result = $this->databaseManager->getColumnsList($_ENV['DATABASE_NAME'], 'users');

        // assert result
        $this->assertIsArray($result);
    }

    /**
     * Test get row by id
     *
     * @return void
     */
    public function testGetRowById(): void
    {
        // result mock
        $resultMock = $this->createMock(Result::class);
        $resultMock->method('fetchAssociative')->willReturn(['id' => 1, 'name' => 'Test Item']);

        // expect executeQuery call
        $this->connectionMock->expects($this->once())->method('executeQuery')
            ->with('SELECT * FROM test_db.test_table WHERE id = :id', ['id' => 1])
            ->willReturn($resultMock);

        // call tested method
        $result = $this->databaseManager->getRowById('test_db', 'test_table', 1);

        // assert result
        $this->assertEquals(['id' => 1, 'name' => 'Test Item'], $result);
    }

    /**
     * Test check if record exists
     *
     * @return void
     */
    public function testDoesRecordExist(): void
    {
        // mock data fetch
        $this->connectionMock->method('fetchAssociative')
            ->willReturn(['count' => 1]);

        // call tested method
        $result = $this->databaseManager->doesRecordExist('test_db', 'test_table', 1);

        // assert result
        $this->assertTrue($result);
    }

    /**
     * Test check if record not exists
     *
     * @return void
     */
    public function testDoesRecordNotExist(): void
    {
        // mock data fetch
        $this->connectionMock->method('fetchAssociative')
            ->willReturn(['count' => 0]);

        // call tested method
        $result = $this->databaseManager->doesRecordExist('test_db', 'test_table', 1);

        // assert result
        $this->assertFalse($result);
    }

    /**
     * Test add row to table
     *
     * @return void
     */
    public function testAddRowToTable(): void
    {
        $formData = [
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'database' => 'test_db',
            'table' => 'test_table'
        ];

        // expect executeQuery call
        $this->connectionMock->expects($this->once())->method('executeQuery')->with(
            $this->stringContains('INSERT INTO test_db.test_table'),
            $this->equalTo(['name' => 'John Doe', 'email' => 'john.doe@example.com'])
        );

        // expect log manager call
        $this->logManagerMock->expects($this->once())->method('log')->with(
            $this->equalTo('database-manager'),
            $this->stringContains('add row to table: test_table'),
            $this->equalTo(LogManager::LEVEL_NOTICE)
        );

        // call tested method
        $this->databaseManager->addRowToTable($formData, 'test_db', 'test_table');
    }

    public function testAddRowToTableThrowsException(): void
    {
        $formData = [
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'database' => 'test_db',
            'table' => 'test_table'
        ];

        // expect executeQuery call
        $this->connectionMock->expects($this->once())->method('executeQuery')
            ->willThrowException(new Exception('Database error'));

        // expect handle error call
        $this->errorManagerMock->expects($this->once())->method('handleError')->with(
            $this->stringContains('error adding row: Database error'),
            $this->equalTo(Response::HTTP_INTERNAL_SERVER_ERROR)
        );

        // call tested method
        $this->databaseManager->addRowToTable($formData, 'test_db', 'test_table');
    }

    /**
     * Test update row by id
     *
     * @return void
     */
    public function testUpdateRowById(): void
    {
        $formData = [
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'database' => 'test_db',
            'table' => 'test_table'
        ];

        // expect executeQuery call
        $this->connectionMock->expects($this->once())->method('executeQuery')->with(
            $this->stringContains('UPDATE test_db.test_table'),
            $this->equalTo(['name' => 'John Doe', 'email' => 'john.doe@example.com', 'id' => 1])
        );

        // expect log manager call
        $this->logManagerMock->expects($this->once())->method('log')->with(
            $this->equalTo('database-manager'),
            $this->stringContains('Updated row with ID: 1'),
            $this->equalTo(LogManager::LEVEL_NOTICE)
        );

        // call tested method
        $this->databaseManager->updateRowById($formData, 'test_db', 'test_table', 1);
    }

    /**
     * Test update row by id throws exception
     *
     * @return void
     */
    public function testUpdateRowByIdThrowsException(): void
    {
        $formData = [
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'database' => 'test_db',
            'table' => 'test_table'
        ];

        // expect executeQuery call with exception throw
        $this->connectionMock->expects($this->once())->method('executeQuery')
            ->willThrowException(new Exception('Database error'));

        // expect handle error call
        $this->errorManagerMock->expects($this->once())->method('handleError')->with(
            $this->stringContains('Error updating row: Database error'),
            $this->equalTo(Response::HTTP_INTERNAL_SERVER_ERROR)
        );

        // call tested method
        $this->databaseManager->updateRowById($formData, 'test_db', 'test_table', 1);
    }

    /**
     * Test delete row by id
     *
     * @return void
     */
    public function testDeleteRowById(): void
    {
        // expect executeStatement call
        $this->connectionMock->expects($this->once())->method('executeStatement')->with(
            $this->stringContains('DELETE FROM test_db.test_table WHERE id = :id'),
            $this->equalTo(['id' => 1])
        );

        // expect log manager call
        $this->logManagerMock->expects($this->once())->method('log')->with(
            $this->equalTo('database-manager'),
            $this->stringContains('deleted row with ID: 1'),
            $this->equalTo(LogManager::LEVEL_NOTICE)
        );

        // call tested method
        $result = $this->databaseManager->deleteRowById('test_db', 'test_table', 1);

        // assert result
        $this->assertIsBool($result);
    }

    /**
     * Test truncate table
     *
     * @return void
     */
    public function testTableTruncate(): void
    {
        // expect executeStatement call
        $this->connectionMock->expects($this->once())->method('executeStatement')->with(
            $this->stringContains('TRUNCATE TABLE test_db.test_table')
        );

        // expect log manager call
        $this->logManagerMock->expects($this->once())->method('log')->with(
            $this->equalTo('database-manager'),
            $this->stringContains('truncated table: test_table'),
            $this->equalTo(LogManager::LEVEL_CRITICAL)
        );

        // call tested method
        $this->databaseManager->tableTruncate('test_db', 'test_table');
    }

    /**
     * Test truncate table throws exception
     *
     * @return void
     */
    public function testTableTruncateThrowsException(): void
    {
        // expect executeStatement call
        $this->connectionMock->expects($this->once())->method('executeStatement')
            ->willThrowException(new Exception('Database error'));

        // expect handleError call
        $this->errorManagerMock->expects($this->once())->method('handleError')->with(
            $this->stringContains('error truncating table: Database error'),
            $this->equalTo(Response::HTTP_INTERNAL_SERVER_ERROR)
        );

        // call tested method
        $this->databaseManager->tableTruncate('test_db', 'test_table');
    }

    /**
     * Test get database dump
     *
     * @return void
     */
    public function testGetDatabaseDump(): void
    {
        // expect fetchAllAssociative call
        $this->connectionMock->expects($this->once())->method('fetchAllAssociative');

        // call tested method
        $result = $this->databaseManager->getDatabaseDump($_ENV['DATABASE_NAME'], true);

        // assert result
        $this->assertIsString($result);
    }

    /**
     * Test split queries
     *
     * @return void
     */
    public function testSplitQueriesSingleQuery(): void
    {
        $sql = 'SELECT * FROM users WHERE id = 1';

        // call tested method
        $queries = $this->databaseManager->splitQueries($sql);

        // assert result
        $this->assertCount(1, $queries);
        $this->assertEquals($sql, $queries[0]);
    }

    /**
     * Test split queries
     *
     * @return void
     */
    public function testSplitQueriesEmptyQuery(): void
    {
        $sql = '';

        // call tested method
        $queries = $this->databaseManager->splitQueries($sql);

        // assert result
        $this->assertCount(0, $queries);
    }
}
