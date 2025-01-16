<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Class Version20250116110324
 *
 * This migration adds sla_history table to the database
 * 
 * @package DoctrineMigrations
 */
final class Version20250116110324 extends AbstractMigration
{
    /**
     * Get the migration description
     *
     * @return string The description of the migration
     */
    public function getDescription(): string
    {
        return 'This migration adds sla_history table to the database';
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
        $this->addSql('CREATE TABLE sla_history (id INT AUTO_INCREMENT NOT NULL, service_name VARCHAR(255) NOT NULL, sla_timeframe VARCHAR(255) NOT NULL, sla_value DOUBLE PRECISION NOT NULL, INDEX sla_history_service_name_idx (service_name), INDEX sla_history_sla_timeframe_idx (sla_timeframe), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
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
        $this->addSql('DROP TABLE sla_history');
    }
}
