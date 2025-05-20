<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Class Version20250520080500
 *
 * This migration adds position column to the todos table
 * 
 * @package DoctrineMigrations
 */
final class Version20250520080500 extends AbstractMigration
{
    /**
     * Get the migration description
     *
     * @return string The description of the migration
     */
    public function getDescription(): string
    {
        return 'Add position column to the todos table for drag and drop reordering';
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
        // add position column to table
        $this->addSql('ALTER TABLE todos ADD position INT NOT NULL DEFAULT 0');

        // set default positions based on id for existing records
        $this->addSql('UPDATE todos SET position = id WHERE position = 0');
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
        $this->addSql('ALTER TABLE todos DROP position');
    }
}
