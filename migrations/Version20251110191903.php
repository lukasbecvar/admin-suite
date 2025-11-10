<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Class Version20251110191903
 *
 * This migration adds table api_access_logs
 * 
 * @package DoctrineMigrations
 */
final class Version20251110191903 extends AbstractMigration
{
    /**
     * Get the migration description
     *
     * @return string The description of migration
     */
    public function getDescription(): string
    {
        return 'Adds table api_access_logs';
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
        $this->addSql('CREATE TABLE api_access_logs (id INT AUTO_INCREMENT NOT NULL, url VARCHAR(255) NOT NULL, method VARCHAR(255) NOT NULL, time DATETIME NOT NULL, user_id INT NOT NULL, INDEX time_idx (time), INDEX user_id_idx (user_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE users CHANGE allow_api_access allow_api_access TINYINT(1) NOT NULL');
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
        $this->addSql('DROP TABLE api_access_logs');
        $this->addSql('ALTER TABLE users CHANGE allow_api_access allow_api_access TINYINT(1) DEFAULT 0 NOT NULL');
    }
}
