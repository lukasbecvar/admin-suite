<?php

namespace App\Manager;

use PDO;
use Exception;
use PDOException;
use App\Util\AppUtil;
use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class DatabaseManager
 *
 * The manager for database operations
 *
 * @package App\Manager
 */
class DatabaseManager
{
    private AppUtil $appUtil;
    private LogManager $logManager;
    private Connection $connection;
    private ErrorManager $errorManager;

    public function __construct(
        AppUtil $appUtil,
        LogManager $logManager,
        Connection $connection,
        ErrorManager $errorManager
    ) {
        $this->appUtil = $appUtil;
        $this->logManager = $logManager;
        $this->connection = $connection;
        $this->errorManager = $errorManager;
    }

    /**
     * Get database connection
     *
     * @return Connection|null The database connection
     */
    public function getDatabaseConnection(): ?Connection
    {
        return $this->connection;
    }

    /**
     * Check if database is down
     *
     * @return bool True if database is down, false otherwise
     */
    public function isDatabaseDown(): bool
    {
        try {
            $this->connection->executeQuery('SELECT 1');
        } catch (Exception) {
            return true;
        }

        return false;
    }

    /**
     * Get list of databases
     *
     * @throws Exception Database list get error
     *
     * @return array<int,array<string,mixed>> The list of databases
     */
    public function getDatabasesList(): array
    {
        $databaseInfo = [];
        $sql = 'SHOW DATABASES';

        try {
            $stmt = $this->connection->executeQuery($sql);
            $databases = $stmt->fetchAllAssociative();

            foreach ($databases as $db) {
                $dbName = $db['Database'];

                // get number of tables
                $sqlTables = "SELECT COUNT(*) as table_count FROM information_schema.tables WHERE table_schema = :dbName";
                $stmtTables = $this->connection->executeQuery($sqlTables, ['dbName' => $dbName]);
                $tableCount = $stmtTables->fetchOne();

                // get size of the database
                $sqlSize = "SELECT SUM(data_length + index_length) / 1024 / 1024 as size_mb 
                            FROM information_schema.tables 
                            WHERE table_schema = :dbName";
                $stmtSize = $this->connection->executeQuery($sqlSize, ['dbName' => $dbName]);
                $sizeMb = $stmtSize->fetchOne();

                // add database info to list
                $databaseInfo[] = [
                    'name' => $dbName,
                    'table_count' => $tableCount,
                    'size_mb' => $sizeMb
                ];
            }

            // log database list get event
            $this->logManager->log(
                name: 'database-manager',
                message: 'get databases list',
                level: LogManager::LEVEL_NOTICE
            );
        } catch (Exception $e) {
            $this->errorManager->handleError(
                message: 'error to get databases list: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        return $databaseInfo;
    }

    /**
     * Check if a database exists
     *
     * @param string $dbName The name of the database
     *
     * @throws Exception Error chacking database exists
     *
     * @return bool True if the database exists, false otherwise
     */
    public function isDatabaseExists(string $dbName): bool
    {
        $sql = 'SHOW DATABASES LIKE :dbName';

        try {
            $stmt = $this->connection->executeQuery($sql, ['dbName' => $dbName]);
            $count = $stmt->fetchOne();

            return $count > 0;
        } catch (Exception $e) {
            $this->errorManager->handleError(
                message: 'error checking if database exists: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Get list of tables in a database
     *
     * @param string $dbName The database name
     *
     * @throws Exception Error getting tables list
     *
     * @return array<int,array<string,mixed>>|null The list of tables
     */
    public function getTablesList(string $dbName): ?array
    {
        try {
            // get table names and sizes
            $sql = "SELECT 
                        table_name AS name, 
                        COALESCE(data_length + index_length, 0) / 1024 / 1024 AS size_mb
                    FROM 
                        information_schema.tables 
                    WHERE 
                        table_schema = :dbName";
            $stmt = $this->connection->executeQuery($sql, ['dbName' => $dbName]);
            $tables = $stmt->fetchAllAssociative();

            // get row counts
            $sqlRows = "SELECT 
                            table_name AS name, 
                            table_rows AS row_count
                        FROM 
                            information_schema.tables 
                        WHERE 
                            table_schema = :dbName";
            $stmtRows = $this->connection->executeQuery($sqlRows, ['dbName' => $dbName]);
            $rows = $stmtRows->fetchAllAssociative();

            // merge results
            $tablesWithRows = [];
            foreach ($tables as $table) {
                $tableName = $table['name'];
                $table['row_count'] = 0;
                foreach ($rows as $row) {
                    if ($row['name'] === $tableName) {
                        $table['row_count'] = $row['row_count'];
                        break;
                    }
                }
                $tablesWithRows[] = $table;
            }

            // log tables list get event
            $this->logManager->log(
                name: 'database-manager',
                message: 'get tables list',
                level: LogManager::LEVEL_NOTICE
            );

            return $tablesWithRows;
        } catch (Exception $e) {
            $this->errorManager->handleError(
                message: 'error to get tables list: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Check if a table exists in a specific database
     *
     * @param string $dbName The name of the database
     * @param string $tableName The name of the table
     *
     * @throws Exception Error checking table exists
     *
     * @return bool True if the table exists, false otherwise
     */
    public function isTableExists(string $dbName, string $tableName): bool
    {
        $sql = "SELECT COUNT(*) 
                FROM information_schema.tables 
                WHERE table_schema = :dbName 
                  AND table_name = :tableName";

        try {
            $stmt = $this->connection->executeQuery($sql, [
                'dbName' => $dbName,
                'tableName' => $tableName,
            ]);
            $count = $stmt->fetchOne();

            return $count > 0;
        } catch (Exception $e) {
            $this->errorManager->handleError(
                message: 'error checking if table exists: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Get number of rows in a specific table in a specific database
     *
     * @param string $dbName The name of the database
     * @param string $tableName The name of the table
     *
     * @throws Exception Error getting table records count
     *
     * @return int The number of rows in the table
     */
    public function getTableRowCount(string $dbName, string $tableName): int
    {
        // check if table exists
        if (!$this->isTableExists($dbName, $tableName)) {
            return 0;
        }

        // select count query
        $sql = "SELECT COUNT(*) FROM {$dbName}.{$tableName}";

        try {
            $stmt = $this->connection->executeQuery($sql);
            $rowCount = $stmt->fetchOne();

            // ensure $rowCount is of a valid type for intval()
            if (is_int($rowCount) || is_float($rowCount) || is_string($rowCount) || is_bool($rowCount) || is_null($rowCount)) {
                return intval($rowCount);
            } else {
                $this->errorManager->handleError(
                    message: 'error retrieving row count from table: invalid type',
                    code: Response::HTTP_INTERNAL_SERVER_ERROR
                );
            }
        } catch (Exception $e) {
            $this->errorManager->handleError(
                message: 'error retrieving row count from table: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Get data from a specific table in a specific database with pagination
     *
     * @param string $dbName The name of the database
     * @param string $tableName The name of the table
     * @param int $page The page number (1-based index)
     *
     * @throws Exception Error getting table data
     *
     * @return array<mixed> Data from the table for the specified page
     */
    public function getTableData(string $dbName, string $tableName, int $page = 1): ?array
    {
        // check if table exists
        if (!$this->isTableExists($dbName, $tableName)) {
            $this->errorManager->handleError(
                message: 'table. ' . $tableName . ' not found in database: ' . $dbName,
                code: Response::HTTP_NOT_FOUND
            );
        }

        // get number of rows in the table
        $pageLimit = (int) $this->appUtil->getEnvValue('LIMIT_CONTENT_PER_PAGE');

        // calculate offset for pagination
        $offset = ($page - 1) * $pageLimit;

        // ensure offset is non-negative
        $offset = max($offset, 0);

        // build the SQL query
        $sql = "SELECT * FROM {$dbName}.{$tableName} LIMIT :offset, :pageSize";

        try {
            $stmt = $this->connection->executeQuery($sql, [
                'offset' => $offset,
                'pageSize' => $pageLimit
            ], [
                'offset' => \Doctrine\DBAL\ParameterType::INTEGER,
                'pageSize' => \Doctrine\DBAL\ParameterType::INTEGER
            ]);

            // log table data get event
            $this->logManager->log(
                name: 'database-manager',
                message: 'get table: ' . $tableName . ' data',
                level: LogManager::LEVEL_NOTICE
            );

            return $stmt->fetchAllAssociative();
        } catch (Exception $e) {
            $this->errorManager->handleError(
                message: 'error retrieving data from table: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Get last page number for a specific table in a specific database
     *
     * @param string $dbName The name of the database
     * @param string $tableName The name of the table
     *
     * @throws Exception Error getting last page number
     *
     * @return int The last page number
     */
    public function getLastPageNumber(string $dbName, string $tableName): int
    {
        // check if table exists
        if (!$this->isTableExists($dbName, $tableName)) {
            $this->errorManager->handleError(
                message: 'table ' . $tableName . ' not found in database: ' . $dbName,
                code: Response::HTTP_NOT_FOUND
            );
        }

        // get number of rows in the table
        $pageLimit = (int) $this->appUtil->getEnvValue('LIMIT_CONTENT_PER_PAGE');

        // build SQL query to get the total number of rows
        $sql = "SELECT COUNT(*) AS total_rows FROM {$dbName}.{$tableName}";

        try {
            $stmt = $this->connection->executeQuery($sql);
            $result = $stmt->fetchAssociative();

            // check if result found
            if (!$result || !isset($result['total_rows'])) {
                $this->errorManager->handleError(
                    message: 'error retrieving the total row count',
                    code: Response::HTTP_INTERNAL_SERVER_ERROR
                );
            }

            $totalRows = $result['total_rows'];

            // calculate total number of pages
            $totalPages = (int) ceil($totalRows / $pageLimit);

            // return last page number
            return max($totalPages, 1);
        } catch (Exception $e) {
            $this->errorManager->handleError(
                message: 'error retrieving the total row count: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Get list of columns in a specific table in a specific database
     *
     * @param string $dbName The name of the database
     * @param string $tableName The name of the table
     *
     * @throws Exception Error getting columns list
     *
     * @return array<int,array<string,mixed>> The list of columns
     */
    public function getColumnsList(string $dbName, string $tableName): array
    {
        // select columns query
        $sql = "SELECT 
                    COLUMN_NAME, 
                    COLUMN_TYPE, 
                    IS_NULLABLE, 
                    COLUMN_KEY, 
                    COLUMN_DEFAULT, 
                    EXTRA 
                FROM information_schema.COLUMNS 
                WHERE TABLE_SCHEMA = :dbName AND TABLE_NAME = :tableName
                ORDER BY ORDINAL_POSITION";

        try {
            // execute select columns query
            $stmt = $this->connection->executeQuery($sql, [
                'dbName' => $dbName,
                'tableName' => $tableName,
            ]);

            // get columns list
            $columns = $stmt->fetchAllAssociative();

            // return columns list
            return $columns;
        } catch (Exception $e) {
            $this->errorManager->handleError(
                message: 'error retrieving columns from table: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Get a row from a specific table in a specific database by ID
     *
     * @param string $databaseName The name of the database
     * @param string $tableName The name of the table
     * @param int $id The ID of the row to retrieve
     *
     * @throws Exception Error getting row by ID
     *
     * @return array<mixed>|null The row data or null if not found
     */
    public function getRowById(string $databaseName, string $tableName, int $id): ?array
    {
        // select row query
        $sql = 'SELECT * FROM ' . $databaseName . '.' . $tableName . ' WHERE id = :id';

        try {
            // execute select row query
            $stmt = $this->connection->executeQuery($sql, ['id' => $id]);

            // get row data
            $row = $stmt->fetchAssociative();

            // check if row exists
            if (!$row) {
                $this->errorManager->handleError(
                    message: 'error retrieving row id: ' . $id . ' in table: ' . $tableName . ' in database: ' . $databaseName . ' row not found',
                    code: Response::HTTP_NOT_FOUND
                );
            }

            return $row;
        } catch (Exception $e) {
            $this->errorManager->handleError(
                message: 'error retrieving row: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Check if a record with the given ID exists in the specified table and database
     *
     * @param string $databaseName The name of the database
     * @param string $tableName The name of the table
     * @param int|string $id The ID to check for
     *
     * @throws Exception Error checking record
     *
     * @return bool True if the record exists, false otherwise
     */
    public function doesRecordExist(string $databaseName, string $tableName, $id): bool
    {
        // select count query
        $sql = sprintf(
            "SELECT COUNT(*) AS count FROM %s.%s WHERE id = :id",
            $this->connection->quoteIdentifier($databaseName),
            $this->connection->quoteIdentifier($tableName)
        );

        try {
            /** @var array<string,int> $result */
            $result = $this->connection->fetchAssociative($sql, ['id' => $id]);

            return (int) $result['count'] > 0;
        } catch (Exception $e) {
            $this->errorManager->handleError(
                message: 'error checking if record exists: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Add row to table
     *
     * @param array<mixed> $formData The submitted form data
     * @param string $databaseName The name of the database
     * @param string $tableName The name of the table
     *
     * @throws Exception Error executing add row query to database
     *
     * @return void
     */
    public function addRowToTable(array $formData, string $databaseName, string $tableName): void
    {
        // unset database and table name from form data
        unset($formData['database']);
        unset($formData['table']);

        try {
            $columnsList = array_keys($formData);
            $placeholders = array_map(fn($column) => ':' . $column, $columnsList);
            $sql = sprintf(
                'INSERT INTO %s.%s (%s) VALUES (%s)',
                $databaseName,
                $tableName,
                implode(',', $columnsList),
                implode(',', $placeholders)
            );

            // execute query
            $this->connection->executeQuery($sql, $formData);

            // log add row event
            $this->logManager->log(
                name: 'database-manager',
                message: 'add row to table: ' . $tableName,
                level: LogManager::LEVEL_NOTICE
            );
        } catch (Exception $e) {
            $this->errorManager->handleError(
                message: 'error adding row: ' . $e->getMessage() . ' to table: ' . $tableName,
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Update row in a specific table in a specific database
     *
     * @param array<mixed> $formData The submitted form data
     * @param string $databaseName The name of the database
     * @param string $tableName The name of the table
     * @param int $id The ID of the row to update
     *
     * @throws Exception Error updating row
     *
     * @return void
     */
    public function updateRowById(array $formData, string $databaseName, string $tableName, int $id): void
    {
        // unset unnecessary keys from form data
        unset($formData['database'], $formData['table'], $formData['page']);

        try {
            // create the list of column placeholders for the update query
            $columnsList = array_keys($formData);
            $setClause = implode(', ', array_map(fn($column) => "$column = :$column", $columnsList));

            // build the SQL query for updating the row
            $sql = sprintf(
                'UPDATE %s.%s SET %s WHERE id = :id',
                $databaseName,
                $tableName,
                $setClause
            );

            // execute query with the data
            $formData['id'] = $id;
            $this->connection->executeQuery($sql, $formData);

            // log update row event
            $this->logManager->log(
                name: 'database-manager',
                message: 'updated row with ID: ' . $id . ' in table: ' . $tableName . ' in database: ' . $databaseName,
                level: LogManager::LEVEL_NOTICE
            );
        } catch (Exception $e) {
            $this->errorManager->handleError(
                message: 'error updating row: ' . $e->getMessage() . ' in table: ' . $tableName . ' in database: ' . $databaseName,
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Delete row from a specific table in a specific database
     *
     * @param string $dbName The name of the database
     * @param string $tableName The name of the table
     * @param int $id The ID of the row to delete
     *
     * @throws Exception Error deleting row
     *
     * @return bool True if the row was deleted successfully, false otherwise
     */
    public function deleteRowById(string $dbName, string $tableName, int $id): bool
    {
        // sql query to delete a row with the specific ID
        $sql = 'DELETE FROM ' . $dbName . '.' . $tableName . ' WHERE id = :id';

        try {
            // execute the delete query
            $this->connection->executeStatement($sql, [
                'id' => $id
            ]);

            // log delete row event
            $this->logManager->log(
                name: 'database-manager',
                message: "deleted row with ID: $id from table: $tableName in database: $dbName",
                level: LogManager::LEVEL_NOTICE
            );

            return true;
        } catch (Exception $e) {
            $this->errorManager->handleError(
                message: 'error deleting row: ' . $e->getMessage() . ' from table: ' . $tableName . ' in database: ' . $dbName,
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Truncate table in a specific database
     *
     * @param string $dbName The name of the database
     * @param string $tableName The name of the table
     *
     * @throws Exception Error truncating table
     *
     * @return void
     */
    public function tableTruncate(string $dbName, string $tableName): void
    {
        // truncate table query
        $sql = 'TRUNCATE TABLE ' . $dbName . '.' . $tableName;

        try {
            // execute truncate table query
            $this->connection->executeStatement($sql);

            // log truncate table event
            $this->logManager->log(
                name: 'database-manager',
                message: "truncated table: $tableName in database: $dbName",
                level: LogManager::LEVEL_CRITICAL
            );
        } catch (Exception $e) {
            $this->errorManager->handleError(
                message: 'error truncating table: ' . $e->getMessage() . ' in database: ' . $dbName,
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Get database dump
     *
     * @param string $dbName The name of the database
     * @param bool $plain Whether to return the dump in plain text format
     *
     * @throws Exception Error getting database dump
     *
     * @return string The database dump
     */
    public function getDatabaseDump(string $dbName, bool $plain = false): string
    {
        // get tables list
        $tables = $this->connection->fetchAllAssociative('SHOW TABLES FROM ' . $this->connection->quoteIdentifier($dbName));

        // build database dump header
        $dump = '-- Database: ' . $dbName . " dumped at: " . date('Y-m-d H:i:s') . " with admin-suite\n\n";
        $dump .= 'SET NAMES utf8mb4;' . "\n\n";
        $dump .= 'DROP DATABASE IF EXISTS ' . $dbName . ";\n";
        $dump .= 'CREATE DATABASE ' . $dbName . ";\n";
        $dump .= 'USE ' . $dbName . ";\n\n";

        try {
            foreach ($tables as $table) {
                /** @var string $tableName */
                $tableName = $table['Tables_in_' . $dbName];
                $createTableStmt = $this->connection->fetchAssociative('SHOW CREATE TABLE ' . $dbName . '.' . $this->connection->quoteIdentifier($tableName));

                if (!$createTableStmt) {
                    $this->errorManager->handleError(
                        message: 'error dumping database: table not found',
                        code: Response::HTTP_INTERNAL_SERVER_ERROR
                    );
                }

                $dump .= $createTableStmt['Create Table'] . ";\n\n";

                if (!$plain) {
                    $rows = $this->connection->fetchAllAssociative('SELECT * FROM ' . $dbName . '.' . $this->connection->quoteIdentifier($tableName));
                    foreach ($rows as $row) {
                        $values = [];
                        foreach ($row as $value) {

                            /** @var string $value */
                            if ($value === null) {
                                $values[] = 'NULL';
                            } else {
                                $values[] = $this->connection->quote($value);
                            }
                        }
                        $values = implode(', ', $values);
                        $dump .= 'INSERT INTO ' . $this->connection->quoteIdentifier($tableName) . ' VALUES (' . $values . ");\n";
                    }
                    $dump .= "\n";
                }
            }

            // log database dump event
            $this->logManager->log(
                name: 'database-manager',
                message: 'get database dump',
                level: LogManager::LEVEL_CRITICAL
            );
        } catch (Exception $e) {
            $this->errorManager->handleError(
                message: 'error dumping database: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        return $dump;
    }

    /**
     * Execute database query
     *
     * @param string $query The query to execute
     *
     * @return string The query output
     */
    public function executeQuery(string $query): string
    {
        try {
            // create a new PDO instance
            $pdo = new PDO(
                'mysql:host=' . $_ENV['DATABASE_HOST'] . ';dbname=' . $_ENV['DATABASE_NAME'],
                $_ENV['DATABASE_USERNAME'],
                $_ENV['DATABASE_PASSWORD'],
                [PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8']
            );
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // execute multiple queries
            $result = '';
            $queries = $this->splitQueries($query); // split query

            // execute all split queries
            foreach ($queries as $q) {
                $q = trim($q);
                if (!empty($q)) {
                    // execute query
                    $stmt = $pdo->query($q);

                    // check if query executed
                    if ($stmt == false) {
                        $this->errorManager->handleError(
                            message: 'error executing query statement: ' . $q,
                            code: Response::HTTP_INTERNAL_SERVER_ERROR
                        );
                    }

                    // fetch result
                    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);

                    // format result
                    if ($data) {
                        foreach ($data as $row) {
                            $result .= implode("\t", $row) . "\n";
                        }
                    }
                }
            }

            // default output
            if (empty($result)) {
                $result = 'Query executed successfully';
            }

            // log execute query event
            $this->logManager->log(
                name: 'database-manager',
                message: 'Executed query',
                level: LogManager::LEVEL_NOTICE
            );

            return $result;
        } catch (PDOException $e) {
            // return error message
            return $e->getMessage();
        }
    }

    /**
     * Split a SQL query into multiple queries
     *
     * @param string $sql The SQL query to split
     *
     * @return array<string> The array of queries
     */
    public function splitQueries(string $sql): array
    {
        $queries = [];
        $currentQuery = '';
        $insideString = false;
        $escaped = false;

        for ($i = 0; $i < strlen($sql); $i++) {
            $char = $sql[$i];

            if ($char === '\\') {
                $escaped = !$escaped;
            } elseif ($char === '\'' || $char === '"') {
                if (!$escaped) {
                    $insideString = !$insideString;
                }
                $escaped = false;
            } elseif ($char === ';' && !$insideString) {
                $queries[] = $currentQuery;
                $currentQuery = '';
                continue;
            }

            $currentQuery .= $char;
        }

        if (!empty($currentQuery)) {
            $queries[] = $currentQuery;
        }

        return $queries;
    }
}
