<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Class Version20250112151646
 *
 * This migration adds the sla_timeframe column to the monitoring_status table
 * 
 * @package DoctrineMigrations
 */
final class Version20250112151646 extends AbstractMigration
{
    /**
     * Get the migration description
     *
     * @return string The description of the migration
     */
    public function getDescription(): string
    {
        return 'Add sla_timeframe column to the monitoring_status table';
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
        // add sla_timeframe column to table
        $this->addSql('ALTER TABLE monitoring_status ADD sla_timeframe VARCHAR(255) NOT NULL');

        // set default value to curent M-Y for existing records
        $this->addSql("UPDATE monitoring_status SET sla_timeframe = '2025-01'");
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
        $this->addSql('ALTER TABLE monitoring_status DROP sla_timeframe');
    }
}
