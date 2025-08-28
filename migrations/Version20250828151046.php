<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Class Version20250828151046
 *
 * This migration adds table services_visitors to the database
 * 
 * @package DoctrineMigrations
 */
final class Version20250828151046 extends AbstractMigration
{
    /**
     * Get the migration description
     *
     * @return string The description of migration
     */
    public function getDescription(): string
    {
        return 'Add table services_visitors to the database';
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
        $this->addSql('CREATE TABLE services_visitors (id INT AUTO_INCREMENT NOT NULL, last_visit_time DATETIME DEFAULT NULL, user_agent VARCHAR(255) NOT NULL, referer VARCHAR(255) NOT NULL, location VARCHAR(255) NOT NULL, ip_address VARCHAR(255) NOT NULL, service_name VARCHAR(255) NOT NULL, INDEX referer_idx (referer), INDEX location_idx (location), INDEX ip_address_idx (ip_address), INDEX service_name_idx (service_name), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
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
        $this->addSql('DROP TABLE services_visitors');
    }
}
