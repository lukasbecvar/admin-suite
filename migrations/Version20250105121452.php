<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Class Version20250105121452
 *
 * Add service_name column to metrics table and set default value for existing records
 * 
 * @package DoctrineMigrations
 */
final class Version20250105121452 extends AbstractMigration
{
    /**
     * Get the migration description
     *
     * @return string The description of the migration
     */
    public function getDescription(): string
    {
        return 'Add service_name column to metrics table and set default value for existing records';
    }

    /**
     * Execute the migration
     *
     * @param Schema $schema The representation of a database schema
     *
     * @return void
     */
    public function up(Schema $schema): void
    {
        // add service_name column to table
        $this->addSql('ALTER TABLE metrics ADD service_name VARCHAR(255) NOT NULL');

        // set default value to "host-system" for existing records
        $this->addSql("UPDATE metrics SET service_name = 'host-system' WHERE service_name IS NULL OR service_name = ''");
    }

    /**
     * Undo the migration
     *
     * @param Schema $schema The representation of a database schema
     *
     * @return void
     */
    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE metrics DROP service_name');
    }
}
