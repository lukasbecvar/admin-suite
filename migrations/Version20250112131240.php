<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Class Version20250112131240
 *
 * This migration adds the down_time column to the monitoring_status table
 * 
 * @package DoctrineMigrations
 */
final class Version20250112131240 extends AbstractMigration
{
    /**
     * Get the migration description
     *
     * @return string The description of the migration
     */
    public function getDescription(): string
    {
        return 'Add down_time column to the monitoring_status table';
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
        $this->addSql('ALTER TABLE monitoring_status ADD down_time INT NOT NULL');

        // set default value to "0 for existing records
        $this->addSql("UPDATE monitoring_status SET down_time = 0 WHERE down_time IS NULL OR down_time = ''");
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
        $this->addSql('ALTER TABLE monitoring_status DROP down_time');
    }
}
