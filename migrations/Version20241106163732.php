<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Class Version20241106163732 migration
 * 
 * The database schema for add metrics table
 * 
 * @package DoctrineMigrations
 */
final class Version20241106163732 extends AbstractMigration
{
    /**
     * Get the description of the migration
     *
     * @return string The description of the migration
     */
    public function getDescription(): string
    {
        return 'Add metrics table';
    }

    /**
     * Execute the migration
     *
     * @param Schema $schema The Doctrine schema
     *
     * @return void
     */
    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE metrics (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, value VARCHAR(255) NOT NULL, time DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
    }

    /**
     * Undo the migration
     *
     * @param Schema $schema The Doctrine schema
     *
     * @return void
     */
    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE metrics');
    }
}
