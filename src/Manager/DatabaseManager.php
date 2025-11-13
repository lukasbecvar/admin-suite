<?php

namespace App\Manager;

use PDO;
use Exception;
use PDOException;
use App\Util\AppUtil;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class DatabaseManager
 *
 * Manager for database operations
 *
 * @package App\Manager
 */
class DatabaseManager
{
    private AppUtil $appUtil;
    private LogManager $logManager;
    private Connection $connection;
    private ErrorManager $errorManager;
    private EntityManagerInterface $entityManager;

    public function __construct(
        AppUtil $appUtil,
        LogManager $logManager,
        Connection $connection,
        ErrorManager $errorManager,
        EntityManagerInterface $entityManager
    ) {
        $this->appUtil = $appUtil;
        $this->logManager = $logManager;
        $this->connection = $connection;
        $this->errorManager = $errorManager;
        $this->entityManager = $entityManager;
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
     * Get MySQL database info and stats
     *
     * @return array<string, mixed>
     */
    public function getDatabaseStats(): array
    {
        $stats = [];

        try {
            $conn = $this->connection;

            // basic server info
            $stats['version'] = $conn->fetchOne('SELECT VERSION()');

            // uptime
            $row = $conn->fetchAssociative("SHOW GLOBAL STATUS LIKE 'Uptime'");
            $stats['uptime'] = $row['Value'] ?? 0;

            // connections
            $row = $conn->fetchAssociative("SHOW STATUS LIKE 'Threads_connected'");
            $stats['threads_connected'] = $row['Value'] ?? 0;

            $row = $conn->fetchAssociative("SHOW VARIABLES LIKE 'max_connections'");
            $stats['max_connections'] = $row['Value'] ?? 0;

            // queries
            $row = $conn->fetchAssociative("SHOW GLOBAL STATUS LIKE 'Queries'");
            $stats['queries'] = $row['Value'] ?? 0;

            $row = $conn->fetchAssociative("SHOW GLOBAL STATUS LIKE 'Slow_queries'");
            $stats['slow_queries'] = $row['Value'] ?? 0;

            // inno-db buffer pool
            $row = $conn->fetchAssociative("SHOW VARIABLES LIKE 'innodb_buffer_pool_size'");
            $bytes = (int)($row['Value'] ?? 0);
            $stats['innodb_buffer_pool_size'] = round($bytes / 1024 / 1024 / 1024, 2); // GB
        } catch (Exception $e) {
            $this->errorManager->handleError(
                message: 'error to get database stats: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        return $stats;
    }

    /**
     * Get list of databases
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
     * Check if database is exists
     *
     * @param string $dbName The name of the database
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
     * Get list of tables in database
     *
     * @param string $dbName The database name
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
     * Check if table is exists in database
     *
     * @param string $dbName The name of the database
     * @param string $tableName The name of the table
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
     * Get number of rows in specific table in specific database
     *
     * @param string $dbName The name of the database
     * @param string $tableName The name of the table
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
     * Get data from a specific table in specific database with pagination
     *
     * @param string $dbName The name of the database
     * @param string $tableName The name of the table
     * @param int $page The page number (1-based index)
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
                'offset' => ParameterType::INTEGER,
                'pageSize' => ParameterType::INTEGER
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
     * Get list of columns in specific table in specific database
     *
     * @param string $dbName The name of the database
     * @param string $tableName The name of the table
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
     * Get row from specific table in specific database by ID
     *
     * @param string $databaseName The name of the database
     * @param string $tableName The name of the table
     * @param int $id The ID of the row to retrieve
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
     * Check if record with the given ID exists in the specified table and database
     *
     * @param string $databaseName The name of the database
     * @param string $tableName The name of the table
     * @param int|string $id The ID to check for
     *
     * @return bool True if the record exists, false otherwise
     */
    public function doesRecordExist(string $databaseName, string $tableName, $id): bool
    {
        // select count query
        $sql = sprintf(
            "SELECT COUNT(*) AS count FROM %s.%s WHERE id = :id",
            $this->connection->quoteSingleIdentifier($databaseName),
            $this->connection->quoteSingleIdentifier($tableName)
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
     * Check if a specific column value exists in the given table
     *
     * @param string $databaseName The database name
     * @param string $tableName The table name
     * @param string $columnName The column name
     * @param int|string $value The value to look for
     *
     * @return bool True when the value exists
     */
    public function doesColumnValueExist(string $databaseName, string $tableName, string $columnName, int|string $value): bool
    {
        $sql = sprintf(
            'SELECT COUNT(*) AS count FROM %s.%s WHERE %s = :value',
            $this->connection->quoteSingleIdentifier($databaseName),
            $this->connection->quoteSingleIdentifier($tableName),
            $this->connection->quoteSingleIdentifier($columnName)
        );

        try {
            $parameterType = $this->getParameterType((string) $value);
            $count = $this->connection->fetchOne($sql, ['value' => $value], ['value' => $parameterType]);

            return (int) $count > 0;
        } catch (Exception $e) {
            $this->errorManager->handleError(
                message: 'error checking if column value exists: ' . $e->getMessage(),
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
     * Update row in specific table in specific database
     *
     * @param array<mixed> $formData The submitted form data
     * @param string $databaseName The name of the database
     * @param string $tableName The name of the table
     * @param int $id The ID of the row to update
     *
     * @return void
     */
    public function updateRowById(array $formData, string $databaseName, string $tableName, int $id): void
    {
        // unset unnecessary keys from form data
        unset($formData['database'], $formData['table'], $formData['page']);

        try {
            // create list of column placeholders for update query
            $columnsList = array_keys($formData);
            $setClause = implode(', ', array_map(fn($column) => "$column = :$column", $columnsList));

            // build SQL query for updating row
            $sql = sprintf(
                'UPDATE %s.%s SET %s WHERE id = :id',
                $databaseName,
                $tableName,
                $setClause
            );

            // execute query with data
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
     * Delete row from specific table in specific database
     *
     * @param string $dbName The name of the database
     * @param string $tableName The name of the table
     * @param int $id The ID of the row to delete
     *
     * @return void
     */
    public function deleteRowById(string $dbName, string $tableName, int $id): void
    {
        // sql query to delete row with specific ID
        $sql = 'DELETE FROM ' . $dbName . '.' . $tableName . ' WHERE id = :id';

        try {
            // execute delete query
            $this->connection->executeStatement($sql, [
                'id' => $id
            ]);

            // log delete row event
            $this->logManager->log(
                name: 'database-manager',
                message: 'deleted row with ID: ' . $id . ' from table: ' . $tableName . ' in database: ' . $dbName,
                level: LogManager::LEVEL_NOTICE
            );
        } catch (Exception $e) {
            $this->errorManager->handleError(
                message: 'error deleting row: ' . $e->getMessage() . ' from table: ' . $tableName . ' in database: ' . $dbName,
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Truncate table in specific database
     *
     * @param string $dbName The name of the database
     * @param string $tableName The name of the table
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
                message: 'truncated table: ' . $tableName . ' in database: ' . $dbName,
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
     * Get entity table name
     *
     * @param string $entityClass The entity class
     *
     * @return string The entity table name
     */
    public function getEntityTableName(string $entityClass): string
    {
        if (!class_exists($entityClass)) {
            $this->errorManager->handleError(
                message: 'entity class not found: ' . $entityClass,
                code: Response::HTTP_NOT_FOUND
            );
        }

        $metadata = $this->entityManager->getClassMetadata($entityClass);
        return $metadata->getTableName();
    }

    /**
     * Get last page number for table
     *
     * @param string $dbName The name of the database
     * @param string $tableName The name of the table
     * @param int|null $limit The row limit per page (null for default value)
     *
     * @return int The last page number
     */
    public function getLastTablePage(string $dbName, string $tableName, ?int $limit = null): int
    {
        if ($limit == null) {
            $limit = (int) $this->appUtil->getEnvValue('LIMIT_CONTENT_PER_PAGE');
        }

        try {
            // escape table name to prevent SQL injection
            $platform = $this->connection->getDatabasePlatform();
            $escapedTableName = $platform->quoteSingleIdentifier($tableName);

            // get total number of rows in table
            $query = "SELECT COUNT(*) FROM $dbName.$escapedTableName";
            $rowCount = $this->connection->fetchOne($query);

            // calculate last page number
            return (int) ceil($rowCount / $limit);
        } catch (Exception $e) {
            $this->errorManager->handleError(
                message: 'error to get last table page: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Recalculate table IDs
     *
     * @param string $tableName The name of the table
     *
     * @return void
     */
    public function recalculateTableIds(string $tableName): void
    {
        try {
            // recalculate ids
            $this->connection->executeQuery('SET @new_id = 0;');
            $this->connection->executeQuery('UPDATE ' . $tableName . ' SET id = (@new_id := @new_id + 1);');

            // get max id
            $maxId = $this->connection->fetchOne('SELECT MAX(id) FROM ' . $tableName . ';');

            // set new auto increment value
            $this->connection->executeQuery('ALTER TABLE ' . $tableName . ' AUTO_INCREMENT = ' . ($maxId + 1) . ';');
        } catch (Exception $e) {
            $this->errorManager->handleError(
                message: 'error recalculating table IDs: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Get database dump
     *
     * @param string $dbName The name of the database
     * @param bool $includeData Whether to return the dump including data
     *
     * @return string The database dump
     */
    public function getDatabaseDump(string $dbName, bool $includeData = false): string
    {
        // get tables list
        $tables = $this->connection->fetchAllAssociative('SHOW TABLES FROM ' . $this->connection->quoteSingleIdentifier($dbName));

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
                $createTableStmt = $this->connection->fetchAssociative('SHOW CREATE TABLE ' . $dbName . '.' . $this->connection->quoteSingleIdentifier($tableName));

                if (!$createTableStmt) {
                    $this->errorManager->handleError(
                        message: 'error dumping database: table not found',
                        code: Response::HTTP_INTERNAL_SERVER_ERROR
                    );
                }

                $dump .= $createTableStmt['Create Table'] . ";\n\n";

                if (!$includeData) {
                    $rows = $this->connection->fetchAllAssociative('SELECT * FROM ' . $dbName . '.' . $this->connection->quoteSingleIdentifier($tableName));
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
                        $dump .= 'INSERT INTO ' . $this->connection->quoteSingleIdentifier($tableName) . ' VALUES (' . $values . ");\n";
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
                message: 'executed query',
                level: LogManager::LEVEL_NOTICE
            );

            return $result;
        } catch (PDOException $e) {
            // return error message
            return $e->getMessage();
        }
    }

    /**
     * Split SQL query into multiple queries
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

    /**
     * Get foreign key metadata for table
     *
     * @param string $dbName The name of the database
     * @param string $tableName The name of the table
     *
     * @return array<string, array<string, string>> The foreign key metadata
     */
    public function getTableForeignKeys(string $dbName, string $tableName): array
    {
        // sql query
        $sql = 'SELECT COLUMN_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
                FROM information_schema.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = :dbName
                  AND TABLE_NAME = :tableName
                  AND REFERENCED_TABLE_NAME IS NOT NULL';

        try {
            $stmt = $this->connection->executeQuery($sql, [
                'dbName' => $dbName,
                'tableName' => $tableName,
            ]);

            $foreignKeys = [];
            foreach ($stmt->fetchAllAssociative() as $row) {
                $columnName = $row['COLUMN_NAME'] ?? null;
                $referencedTable = $row['REFERENCED_TABLE_NAME'] ?? null;
                $referencedColumn = $row['REFERENCED_COLUMN_NAME'] ?? null;

                if (is_string($columnName) && is_string($referencedTable) && is_string($referencedColumn)) {
                    $foreignKeys[$columnName] = [
                        'referencedTable' => $referencedTable,
                        'referencedColumn' => $referencedColumn
                    ];
                }
            }

            return $foreignKeys;
        } catch (Exception $e) {
            $this->errorManager->handleError(
                message: 'error retrieving foreign keys from table: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Get primary key column name for table
     *
     * @param string $dbName The name of the database
     * @param string $tableName The name of the table
     *
     * @return string|null The primary key column name or null if not found
     */
    public function getPrimaryKeyColumn(string $dbName, string $tableName): ?string
    {
        $sql = 'SELECT COLUMN_NAME
                FROM information_schema.KEY_COLUMN_USAGE
                WHERE TABLE_SCHEMA = :dbName
                  AND TABLE_NAME = :tableName
                  AND CONSTRAINT_NAME = \'PRIMARY\'
                LIMIT 1';

        try {
            $stmt = $this->connection->executeQuery($sql, [
                'dbName' => $dbName,
                'tableName' => $tableName,
            ]);

            /** @var string|null $columnName */
            $columnName = $stmt->fetchOne();

            return is_string($columnName) ? $columnName : null;
        } catch (Exception $e) {
            $this->errorManager->handleError(
                message: 'error retrieving primary key column: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Resolve page number for a given primary key value
     *
     * @param string $dbName The name of the database
     * @param string $tableName The name of the table
     * @param string $columnName The name of the column
     * @param string $value The value of the column
     * @param int $pageLimit The page limit
     *
     * @return int|null The page number or null if not found
     */
    public function getPageForColumnValue(string $dbName, string $tableName, string $columnName, string $value, int $pageLimit): ?int
    {
        if ($pageLimit <= 0) {
            return null;
        }

        // check if identifiers are valid
        if (!$this->isValidIdentifier($dbName) || !$this->isValidIdentifier($tableName) || !$this->isValidIdentifier($columnName)) {
            $this->errorManager->handleError('Invalid identifier provided', Response::HTTP_BAD_REQUEST);
        }

        $primaryKey = $this->getPrimaryKeyColumn($dbName, $tableName);
        if ($primaryKey === null || $primaryKey !== $columnName) {
            return null;
        }

        $paramType = $this->getParameterType($value);

        try {
            $existsStmt = $this->connection->executeQuery(
                "SELECT COUNT(*) FROM {$dbName}.{$tableName} WHERE {$columnName} = :value",
                ['value' => $value],
                ['value' => $paramType]
            );
            $exists = (int) $existsStmt->fetchOne();
            if ($exists === 0) {
                return null;
            }

            $countStmt = $this->connection->executeQuery(
                "SELECT COUNT(*) FROM {$dbName}.{$tableName} WHERE {$columnName} < :value",
                ['value' => $value],
                ['value' => $paramType]
            );

            $itemsBefore = (int) $countStmt->fetchOne();
            $position = $itemsBefore + 1;

            return (int) ceil($position / $pageLimit);
        } catch (Exception $e) {
            $this->errorManager->handleError(
                message: 'error resolving table page for column value: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }
    }

    /**
     * Check if a column type represents a numeric value
     *
     * @param string $columnType The database column type
     *
     * @return bool True when the column expects numbers
     */
    public function isNumericColumnType(string $columnType): bool
    {
        $normalizedType = strtolower($columnType);
        $numericHints = ['int', 'decimal', 'numeric', 'double', 'float', 'real'];

        foreach ($numericHints as $hint) {
            if (str_contains($normalizedType, $hint)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if identifier is valid
     *
     * @param string $identifier The identifier to check
     *
     * @return bool True if identifier is valid, false otherwise
     */
    private function isValidIdentifier(string $identifier): bool
    {
        return preg_match('/^[A-Za-z0-9_]+$/', $identifier) === 1;
    }

    /**
     * Get the parameter type for a given value
     *
     * @param string $value The value to check
     *
     * @return ParameterType The parameter type
     */
    private function getParameterType(string $value): ParameterType
    {
        return ctype_digit($value) ? ParameterType::INTEGER : ParameterType::STRING;
    }
}
