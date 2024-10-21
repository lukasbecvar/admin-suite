<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Class Version20241020170527 migration
 * 
 * The database schema for add notifications_subscribers table
 * 
 * @package DoctrineMigrations
 */
final class Version20241020170527 extends AbstractMigration
{
    /**
     * Get the description of the migration
     *
     * @return string The description of the migration
     */
    public function getDescription(): string
    {
        return 'Add notifications_subscribers table';
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
        $this->addSql('CREATE TABLE notifications_subscribers (id INT AUTO_INCREMENT NOT NULL, endpoint VARCHAR(255) NOT NULL, public_key VARCHAR(255) NOT NULL, auth_token VARCHAR(255) NOT NULL, subscribed_time DATETIME DEFAULT NULL, status VARCHAR(255) NOT NULL, user_id INT NOT NULL, INDEX status_idx (status), INDEX user_id_idx (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
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
        $this->addSql('DROP TABLE notifications_subscribers');
    }
}
