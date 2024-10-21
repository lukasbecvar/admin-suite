<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Class Version20240925121056 migration
 * 
 * The database schema for add indexes to the tables
 * 
 * @package DoctrineMigrations
 */
final class Version20240925121056 extends AbstractMigration
{
    /**
     * Get the description of the migration
     *
     * @return string The description of the migration
     */
    public function getDescription(): string
    {
        return 'Migration for add indexes to the tables';
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
        $this->addSql('CREATE INDEX ban_list_status_idx ON ban_list (status)');
        $this->addSql('CREATE INDEX ban_list_banned_by_id_idx ON ban_list (banned_by_id)');
        $this->addSql('CREATE INDEX ban_list_banned_user_id_idx ON ban_list (banned_user_id)');
        $this->addSql('CREATE INDEX logs_name_idx ON logs (name)');
        $this->addSql('CREATE INDEX logs_time_idx ON logs (time)');
        $this->addSql('CREATE INDEX logs_status_idx ON logs (status)');
        $this->addSql('CREATE INDEX logs_user_id_idx ON logs (user_id)');
        $this->addSql('CREATE INDEX logs_user_agent_idx ON logs (user_agent)');
        $this->addSql('CREATE INDEX logs_ip_address_idx ON logs (ip_address)');
        $this->addSql('CREATE INDEX logs_status_idx ON monitoring (status)');
        $this->addSql('CREATE INDEX logs_service_name_idx ON monitoring (service_name)');
        $this->addSql('CREATE INDEX logs_status_idx ON todos (status)');
        $this->addSql('CREATE INDEX logs_user_id_idx ON todos (user_id)');
        $this->addSql('CREATE INDEX logs_role_idx ON users (role)');
        $this->addSql('CREATE INDEX logs_token_idx ON users (token)');
        $this->addSql('CREATE INDEX logs_username_idx ON users (username)');
        $this->addSql('CREATE INDEX logs_ip_address_idx ON users (ip_address)');
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
        $this->addSql('DROP INDEX logs_status_idx ON todos');
        $this->addSql('DROP INDEX logs_user_id_idx ON todos');
        $this->addSql('DROP INDEX ban_list_status_idx ON ban_list');
        $this->addSql('DROP INDEX ban_list_banned_by_id_idx ON ban_list');
        $this->addSql('DROP INDEX ban_list_banned_user_id_idx ON ban_list');
        $this->addSql('DROP INDEX logs_status_idx ON monitoring');
        $this->addSql('DROP INDEX logs_service_name_idx ON monitoring');
        $this->addSql('DROP INDEX logs_role_idx ON users');
        $this->addSql('DROP INDEX logs_token_idx ON users');
        $this->addSql('DROP INDEX logs_username_idx ON users');
        $this->addSql('DROP INDEX logs_ip_address_idx ON users');
        $this->addSql('DROP INDEX logs_name_idx ON logs');
        $this->addSql('DROP INDEX logs_time_idx ON logs');
        $this->addSql('DROP INDEX logs_status_idx ON logs');
        $this->addSql('DROP INDEX logs_user_id_idx ON logs');
        $this->addSql('DROP INDEX logs_user_agent_idx ON logs');
        $this->addSql('DROP INDEX logs_ip_address_idx ON logs');
    }
}
