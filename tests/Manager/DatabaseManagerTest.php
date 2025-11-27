<?php

namespace App\Tests\Manager;

use Exception;
use App\Entity\User;
use App\Util\AppUtil;
use Doctrine\DBAL\Result;
use App\Manager\LogManager;
use Doctrine\DBAL\Connection;
use App\Manager\ErrorManager;
use PHPUnit\Framework\TestCase;
use App\Manager\DatabaseManager;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class DatabaseManagerTest
 *
 * Test cases for database manager
 *
 * @package App\Tests\Manager
 */
#[CoversClass(DatabaseManager::class)]
class DatabaseManagerTest extends TestCase
{
    private DatabaseManager $databaseManager;
    private AppUtil & MockObject $appUtilMock;
    private LogManager & MockObject $logManagerMock;
    private Connection & MockObject $connectionMock;
    private ErrorManager & MockObject $errorManagerMock;
    private EntityManagerInterface & MockObject $entityManagerMock;

    protected function setUp(): void
    {
        // mock dependencies
        $this->appUtilMock = $this->createMock(AppUtil::class);
        $this->connectionMock = $this->createMock(Connection::class);
        $this->logManagerMock = $this->createMock(LogManager::class);
        $this->errorManagerMock = $this->createMock(ErrorManager::class);
        $this->entityManagerMock = $this->createMock(EntityManagerInterface::class);

        // initialize database manager
        $this->databaseManager = new DatabaseManager(
            $this->appUtilMock,
            $this->logManagerMock,
            $this->connectionMock,
            $this->errorManagerMock,
            $this->entityManagerMock
        );
    }

    /**
     * Test get database connection
     *
     * @return void
     */
    public function testGetDatabaseConnection(): void
    {
        // call tested method
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

        // call tested method
        $result = $this->databaseManager->isDatabaseDown();

        // assert result
        $this->assertTrue($result);
    }

    /**
     * Test get database stats
     *
     * @return void
     */
    public function testGetDatabaseStats(): void
    {
        // call tested method
        $result = $this->databaseManager->getDatabaseStats();

        // assert result
        $this->assertIsArray($result);
        $this->assertArrayHasKey('version', $result);
        $this->assertArrayHasKey('uptime', $result);
        $this->assertArrayHasKey('threads_connected', $result);
        $this->assertArrayHasKey('max_connections', $result);
        $this->assertArrayHasKey('queries', $result);
        $this->assertArrayHasKey('slow_queries', $result);
        $this->assertArrayHasKey('innodb_buffer_pool_size', $result);
    }

    /**
     * Test get databases list
     *
     * @return void
     */
    public function testGetDatabasesList(): void
    {
        // expect log manager call
        $this->logManagerMock->expects($this->once())->method('log')->with(
            'database-manager',
            'get databases list',
            LogManager::LEVEL_NOTICE
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
            'database-manager',
            'get tables list',
            LogManager::LEVEL_NOTICE
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
        // mock limit content per page
        $this->appUtilMock->method('getEnvValue')->with('LIMIT_CONTENT_PER_PAGE')->willReturn('10');

        // mock table exists result
        $tableExistsResult = $this->createMock(Result::class);
        $tableExistsResult->method('fetchOne')->willReturn(1);
        $dataResult = $this->createMock(Result::class);
        $dataResult->method('fetchAllAssociative')->willReturn([['id' => 1]]);

        // expect executeQuery calls
        $this->connectionMock->expects($this->exactly(2))->method('executeQuery')
            ->willReturnOnConsecutiveCalls($tableExistsResult, $dataResult);

        // expect log manager call
        $this->logManagerMock->expects($this->once())->method('log');

        // call tested method
        $result = $this->databaseManager->getTableData('test_db', 'users', 1);

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
        $this->connectionMock->method('fetchAssociative')->willReturn(['count' => 1]);

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
        $this->connectionMock->method('fetchAssociative')->willReturn(['count' => 0]);

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
        // testing data
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
            'database-manager',
            'add row to table: test_table',
            LogManager::LEVEL_NOTICE
        );

        // call tested method
        $this->databaseManager->addRowToTable($formData, 'test_db', 'test_table');
    }

    /**
     * Test add row to table throws exception
     *
     * @return void
     */
    public function testAddRowToTableThrowsException(): void
    {
        // testing data
        $formData = [
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'database' => 'test_db',
            'table' => 'test_table'
        ];

        // expect executeQuery call
        $this->connectionMock->expects($this->once())->method('executeQuery')->willThrowException(
            new Exception('Database error')
        );

        // expect handle error call
        $this->errorManagerMock->expects($this->once())->method('handleError')->with(
            'error adding row: Database error to table: test_table',
            Response::HTTP_INTERNAL_SERVER_ERROR
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
        // testing data
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
            'database-manager',
            'updated row with ID: 1 in table: test_table in database: test_db',
            LogManager::LEVEL_NOTICE
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
        // testing data
        $formData = [
            'name' => 'John Doe',
            'email' => 'john.doe@example.com',
            'database' => 'test_db',
            'table' => 'test_table'
        ];

        // expect executeQuery call with exception throw
        $this->connectionMock->expects($this->once())->method('executeQuery')->willThrowException(
            new Exception('Database error')
        );

        // expect handle error call
        $this->errorManagerMock->expects($this->once())->method('handleError')->with(
            'error updating row: Database error in table: test_table in database: test_db',
            Response::HTTP_INTERNAL_SERVER_ERROR
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
            'database-manager',
            'deleted row with ID: 1 from table: test_table in database: test_db',
            LogManager::LEVEL_NOTICE
        );

        // call tested method
        $this->databaseManager->deleteRowById('test_db', 'test_table', 1);
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
            'database-manager',
            'truncated table: test_table in database: test_db',
            LogManager::LEVEL_CRITICAL
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
        // mock isTableExists to return true (via executeQuery)
        $isTableExistsResult = $this->createMock(Result::class);
        $isTableExistsResult->method('fetchOne')->willReturn(1); // Table exists

        // expect executeQuery for isTableExists
        $this->connectionMock->expects($this->once())->method('executeQuery')->willReturn($isTableExistsResult);

        // expect executeStatement to throw exception
        $this->connectionMock->expects($this->once())->method('executeStatement')->willThrowException(
            new Exception('Database error')
        );

        // expect handleError call
        $this->errorManagerMock->expects($this->once())->method('handleError')->with(
            'error truncating table: Database error in database: test_db',
            Response::HTTP_INTERNAL_SERVER_ERROR
        );

        // call tested method
        $this->databaseManager->tableTruncate('test_db', 'test_table');
    }

    /**
     * Test truncate table throws table not found exception
     *
     * @return void
     */
    public function testTableTruncateThrowsTableNotFoundException(): void
    {
        // mock isValidIdentifier and isTableExists to trigger the "table not found" error
        $this->connectionMock->expects($this->once())->method('executeQuery')->willReturn(
            $this->createConfiguredMock(Result::class, ['fetchOne' => 0]) // For isTableExists, table does NOT exist
        );

        // expect handleError call
        $this->errorManagerMock->expects($this->once())->method('handleError')->with(
            'table not found: test_db.test_table',
            Response::HTTP_NOT_FOUND
        );

        // call tested method
        $this->databaseManager->tableTruncate('test_db', 'test_table');
    }

    /**
     * Test get entity table name
     *
     * @return void
     */
    public function testGetEntityTableName(): void
    {
        // call tested method
        $result = $this->databaseManager->getEntityTableName(User::class);

        // assert result
        $this->assertIsString($result);
    }

    /**
     * Test recalculate table IDs
     *
     * @return void
     */
    public function testRecalculateTableIds(): void
    {
        // expect executeQuery call
        $this->connectionMock->expects($this->exactly(3))->method('executeQuery');

        // call tested method
        $this->databaseManager->recalculateTableIds('users');
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
     * Test execute query
     *
     * @return void
     */
    public function testExecuteQuery(): void
    {
        // call tested method
        $result = $this->databaseManager->executeQuery('SELECT * FROM users');

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
        // call tested method
        $queries = $this->databaseManager->splitQueries('');

        // assert result
        $this->assertCount(0, $queries);
    }

    /**
     * Test get table foreign keys
     *
     * @return void
     */
    public function testGetTableForeignKeys(): void
    {
        $resultMock = $this->createMock(Result::class);
        $resultMock->method('fetchAllAssociative')->willReturn([[
            'COLUMN_NAME' => 'user_id',
            'REFERENCED_TABLE_NAME' => 'users',
            'REFERENCED_COLUMN_NAME' => 'id'
        ]]);

        // expect query call
        $this->connectionMock->expects($this->once())->method('executeQuery')->willReturn($resultMock);

        // call tested method
        $foreignKeys = $this->databaseManager->getTableForeignKeys('test_db', 'logs');

        // assert result
        $this->assertArrayHasKey('user_id', $foreignKeys);
        $this->assertSame('users', $foreignKeys['user_id']['referencedTable']);
        $this->assertSame('id', $foreignKeys['user_id']['referencedColumn']);
    }

    /**
     * Test get primary key column
     *
     * @return void
     */
    public function testGetPrimaryKeyColumn(): void
    {
        // mock primary key result
        $resultMock = $this->createMock(Result::class);
        $resultMock->method('fetchOne')->willReturn('id');

        // expect query call
        $this->connectionMock->expects($this->once())->method('executeQuery')->willReturn($resultMock);

        // call tested method
        $primaryKey = $this->databaseManager->getPrimaryKeyColumn('test_db', 'users');

        // assert result
        $this->assertSame('id', $primaryKey);
    }

    /**
     * Test get page for column value
     *
     * @return void
     */
    public function testGetPageForColumnValue(): void
    {
        // mock fetch results
        $primaryResult = $this->createMock(Result::class);
        $primaryResult->method('fetchOne')->willReturn('id');
        $existsResult = $this->createMock(Result::class);
        $existsResult->method('fetchOne')->willReturn(1);
        $countResult = $this->createMock(Result::class);
        $countResult->method('fetchOne')->willReturn(14);

        // expect query calls
        $this->connectionMock->expects($this->exactly(3))->method('executeQuery')
            ->willReturnOnConsecutiveCalls($primaryResult, $existsResult, $countResult);

        // call tested method
        $page = $this->databaseManager->getPageForColumnValue('test_db', 'users', 'id', '15', 10);

        // assert result
        $this->assertSame(2, $page);
    }

    /**
     * Test get page for column value when primary key missing
     *
     * @return void
     */
    public function testGetPageForColumnValueReturnsNullWhenPrimaryKeyMissing(): void
    {
        $primaryResult = $this->createMock(Result::class);
        $primaryResult->method('fetchOne')->willReturn(null);

        // expect query call
        $this->connectionMock->expects($this->once())->method('executeQuery')->willReturn($primaryResult);

        // call tested method
        $page = $this->databaseManager->getPageForColumnValue('test_db', 'users', 'id', '1', 10);

        // assert result
        $this->assertNull($page);
    }
}
