<?php

namespace App\Manager;

use Doctrine\DBAL\Connection;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class DatabaseManager
 *
 * The manager for database connection
 *
 * @package App\Manager
 */
class DatabaseManager
{
    private Connection $connection;
    private ErrorManager $errorManager;

    public function __construct(Connection $connection, ErrorManager $errorManager)
    {
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
        } catch (\Exception) {
            return true;
        }

        return false;
    }

    /**
     * Get the list of databases
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

                // get the number of tables
                $sqlTables = "SELECT COUNT(*) as table_count FROM information_schema.tables WHERE table_schema = :dbName";
                $stmtTables = $this->connection->executeQuery($sqlTables, ['dbName' => $dbName]);
                $tableCount = $stmtTables->fetchOne();

                // get the size of the database
                $sqlSize = "SELECT SUM(data_length + index_length) / 1024 / 1024 as size_mb 
                            FROM information_schema.tables 
                            WHERE table_schema = :dbName";
                $stmtSize = $this->connection->executeQuery($sqlSize, ['dbName' => $dbName]);
                $sizeMb = $stmtSize->fetchOne();

                $databaseInfo[] = [
                    'name' => $dbName,
                    'table_count' => $tableCount,
                    'size_mb' => $sizeMb
                ];
            }
        } catch (\Exception $e) {
            $this->errorManager->handleError(
                message: 'error to get databases list: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        return $databaseInfo;
    }

    /**
     * Get the list of tables in a database
     *
     * @param string $dbName The database name
     *
     * @return array<int,array<string,mixed>>|null The list of tables
     */
    public function getTablesList(string $dbName): ?array
    {
        $sql = "SELECT 
                    table_name AS name, 
                    COALESCE(SUM(data_length + index_length) / 1024 / 1024, 0) AS size_mb 
                FROM 
                    information_schema.tables 
                WHERE 
                    table_schema = :dbName 
                GROUP BY 
                    table_name";

        try {
            $stmt = $this->connection->executeQuery($sql, ['dbName' => $dbName]);
            return $stmt->fetchAllAssociative();
        } catch (\Exception $e) {
            $this->errorManager->handleError(
                message: 'error to get tables list: ' . $e->getMessage(),
                code: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        }

        return null;
    }
}
