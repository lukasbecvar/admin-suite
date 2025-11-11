<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Class Version20251111200719
 *
 * This migration adds sent_notifications_logs table
 * 
 * @package DoctrineMigrations
 */
final class Version20251111200719 extends AbstractMigration
{
    /**
     * Get the migration description
     *
     * @return string The description of migration
     */
    public function getDescription(): string
    {
        return 'Adds sent_notifications_logs table';
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
        $this->addSql('CREATE TABLE sent_notifications_logs (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, message LONGTEXT NOT NULL, sent_time DATETIME NOT NULL, receiver_id INT NOT NULL, INDEX sent_notifications_logs_receiver_id_idx (receiver_id), INDEX sent_notifications_logs_sent_time_idx (sent_time), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
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
        $this->addSql('DROP TABLE sent_notifications_logs');
    }
}
