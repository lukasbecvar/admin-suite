<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Class Version20251111223246
 *
 * This migration adds missing foreign keys for better table linking
 * 
 * @package DoctrineMigrations
 */
final class Version20251111223246 extends AbstractMigration
{
    /**
     * Get the migration description
     *
     * @return string The description of migration
     */
    public function getDescription(): string
    {
        return 'Adds missing foreign keys for better table linking';
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
        $this->addSql('ALTER TABLE ban_list CHANGE banned_by_id banned_by_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE logs CHANGE user_id user_id INT DEFAULT NULL');

        // normalize legacy rows before enabling the new constraints
        $this->addSql('UPDATE logs SET user_id = NULL WHERE user_id = 0');
        $this->addSql('UPDATE logs SET user_id = NULL WHERE user_id IS NOT NULL AND user_id NOT IN (SELECT id FROM users)');
        $this->addSql('DELETE FROM api_access_logs WHERE user_id NOT IN (SELECT id FROM users)');
        $this->addSql('DELETE FROM notifications_subscribers WHERE user_id NOT IN (SELECT id FROM users)');
        $this->addSql('DELETE FROM sent_notifications_logs WHERE receiver_id NOT IN (SELECT id FROM users)');
        $this->addSql('DELETE FROM todos WHERE user_id NOT IN (SELECT id FROM users)');
        $this->addSql('DELETE FROM ban_list WHERE banned_user_id NOT IN (SELECT id FROM users)');
        $this->addSql('UPDATE ban_list SET banned_by_id = NULL WHERE banned_by_id IS NOT NULL AND banned_by_id NOT IN (SELECT id FROM users)');

        // enable the new constraints
        $this->addSql('ALTER TABLE api_access_logs ADD CONSTRAINT FK_6C212AD4A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE ban_list ADD CONSTRAINT FK_371C2ECA386B8E7 FOREIGN KEY (banned_by_id) REFERENCES users (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE ban_list ADD CONSTRAINT FK_371C2ECA2CE9C1AD FOREIGN KEY (banned_user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE logs ADD CONSTRAINT FK_F08FC65CA76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE notifications_subscribers ADD CONSTRAINT FK_59BABC69A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE sent_notifications_logs ADD CONSTRAINT FK_5B3704F3CD53EDB6 FOREIGN KEY (receiver_id) REFERENCES users (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE todos ADD CONSTRAINT FK_CD826255A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE');
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
        $this->addSql('ALTER TABLE ban_list DROP FOREIGN KEY FK_371C2ECA386B8E7');
        $this->addSql('ALTER TABLE api_access_logs DROP FOREIGN KEY FK_6C212AD4A76ED395');
        $this->addSql('ALTER TABLE ban_list DROP FOREIGN KEY FK_371C2ECA2CE9C1AD');
        $this->addSql('ALTER TABLE logs DROP FOREIGN KEY FK_F08FC65CA76ED395');
        $this->addSql('ALTER TABLE logs CHANGE user_id user_id INT NOT NULL');
        $this->addSql('ALTER TABLE ban_list CHANGE banned_by_id banned_by_id INT NOT NULL');
        $this->addSql('ALTER TABLE notifications_subscribers DROP FOREIGN KEY FK_59BABC69A76ED395');
        $this->addSql('ALTER TABLE sent_notifications_logs DROP FOREIGN KEY FK_5B3704F3CD53EDB6');
        $this->addSql('ALTER TABLE todos DROP FOREIGN KEY FK_CD826255A76ED395');
    }
}
