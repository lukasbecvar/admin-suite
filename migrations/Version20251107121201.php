<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Class Version20251107121201
 *
 * This migration adds allow_api_access field to users
 * 
 * @package DoctrineMigrations
 */
final class Version20251107121201 extends AbstractMigration
{
    /**
     * Get the migration description
     *
     * @return string The description of migration
     */
    public function getDescription(): string
    {
        return 'Add allow_api_access field to users and set all existing users to false';
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
        // add column with default 0 (false)
        $this->addSql('ALTER TABLE users ADD allow_api_access TINYINT(1) DEFAULT 0 NOT NULL');

        // set all existing rows explicitly to 0 (just safety)
        $this->addSql('UPDATE users SET allow_api_access = 0 WHERE allow_api_access IS NULL');
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
        $this->addSql('ALTER TABLE users DROP allow_api_access');
    }
}
