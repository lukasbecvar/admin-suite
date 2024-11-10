<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Class Version20241110143215 migration
 * 
 * The database schema migration
 * 
 * @package DoctrineMigrations
 */
final class Version20241110143215 extends AbstractMigration
{
    /**
     * Get the description of the migration
     *
     * @return string The description of the migration
     */
    public function getDescription(): string
    {
        return 'The database schema';
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
        $this->addSql('CREATE TABLE ban_list (id INT AUTO_INCREMENT NOT NULL, reason VARCHAR(255) NOT NULL, status VARCHAR(255) NOT NULL, time DATETIME NOT NULL, banned_by_id INT NOT NULL, banned_user_id INT NOT NULL, INDEX ban_list_status_idx (status), INDEX ban_list_banned_by_id_idx (banned_by_id), INDEX ban_list_banned_user_id_idx (banned_user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE logs (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, message LONGTEXT NOT NULL, time DATETIME NOT NULL, user_agent VARCHAR(255) NOT NULL, ip_address VARCHAR(255) NOT NULL, level INT NOT NULL, user_id INT NOT NULL, status VARCHAR(255) NOT NULL, INDEX logs_name_idx (name), INDEX logs_time_idx (time), INDEX logs_status_idx (status), INDEX logs_user_id_idx (user_id), INDEX logs_user_agent_idx (user_agent), INDEX logs_ip_address_idx (ip_address), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE metrics (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, value VARCHAR(255) NOT NULL, time DATETIME NOT NULL, INDEX metrics_name_idx (name), INDEX metrics_time_idx (time), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE monitoring_status (id INT AUTO_INCREMENT NOT NULL, service_name VARCHAR(255) NOT NULL, message VARCHAR(255) NOT NULL, status VARCHAR(255) NOT NULL, last_update_time DATETIME NOT NULL, INDEX monitoring_status_status_idx (status), INDEX monitoring_status_service_name_idx (service_name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE notifications_subscribers (id INT AUTO_INCREMENT NOT NULL, endpoint VARCHAR(255) NOT NULL, public_key VARCHAR(255) NOT NULL, auth_token VARCHAR(255) NOT NULL, subscribed_time DATETIME DEFAULT NULL, status VARCHAR(255) NOT NULL, user_id INT NOT NULL, INDEX notifications_subscribers_status_idx (status), INDEX notifications_subscribers_user_id_idx (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE todos (id INT AUTO_INCREMENT NOT NULL, todo_text LONGTEXT NOT NULL, added_time DATETIME NOT NULL, completed_time DATETIME DEFAULT NULL, status VARCHAR(255) NOT NULL, user_id INT NOT NULL, INDEX todos_status_idx (status), INDEX todos_user_id_idx (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE users (id INT AUTO_INCREMENT NOT NULL, username VARCHAR(255) NOT NULL, password VARCHAR(255) NOT NULL, role VARCHAR(255) NOT NULL, ip_address VARCHAR(255) NOT NULL, user_agent VARCHAR(255) NOT NULL, register_time DATETIME NOT NULL, last_login_time DATETIME NOT NULL, token VARCHAR(255) NOT NULL, profile_pic LONGTEXT NOT NULL, UNIQUE INDEX UNIQ_1483A5E9F85E0677 (username), UNIQUE INDEX UNIQ_1483A5E95F37A13B (token), INDEX users_role_idx (role), INDEX users_token_idx (token), INDEX users_username_idx (username), INDEX users_ip_address_idx (ip_address), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
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
        $this->addSql('DROP TABLE ban_list');
        $this->addSql('DROP TABLE logs');
        $this->addSql('DROP TABLE metrics');
        $this->addSql('DROP TABLE monitoring_status');
        $this->addSql('DROP TABLE notifications_subscribers');
        $this->addSql('DROP TABLE todos');
        $this->addSql('DROP TABLE users');
    }
}
