<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Class Version20240806183654 migration
 * 
 * The default database schema for the application
 * 
 * @package DoctrineMigrations
 */
final class Version20240911132743 extends AbstractMigration
{
    /**
     * Get the description of the migration
     *
     * @return string The description of the migration
     */
    public function getDescription(): string
    {
        return 'Default database schema';
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
        $this->addSql('CREATE TABLE ban_list (id INT AUTO_INCREMENT NOT NULL, reason VARCHAR(255) NOT NULL, status VARCHAR(255) NOT NULL, time DATETIME NOT NULL, banned_by_id INT NOT NULL, banned_user_id INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE logs (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, message LONGTEXT NOT NULL, time DATETIME NOT NULL, user_agent VARCHAR(255) NOT NULL, ip_address VARCHAR(255) NOT NULL, level INT NOT NULL, user_id INT NOT NULL, status VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE monitoring_status (id INT AUTO_INCREMENT NOT NULL, service_name VARCHAR(255) NOT NULL, message VARCHAR(255) NOT NULL, status VARCHAR(255) NOT NULL, last_update_time DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE todos (id INT AUTO_INCREMENT NOT NULL, todo_text LONGTEXT NOT NULL, added_time DATETIME NOT NULL, completed_time DATETIME DEFAULT NULL, status VARCHAR(255) NOT NULL, user_id INT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE users (id INT AUTO_INCREMENT NOT NULL, username VARCHAR(255) NOT NULL, password VARCHAR(255) NOT NULL, role VARCHAR(255) NOT NULL, ip_address VARCHAR(255) NOT NULL, user_agent VARCHAR(255) NOT NULL, register_time DATETIME NOT NULL, last_login_time DATETIME NOT NULL, token VARCHAR(255) NOT NULL, profile_pic LONGTEXT NOT NULL, UNIQUE INDEX UNIQ_1483A5E9F85E0677 (username), UNIQUE INDEX UNIQ_1483A5E95F37A13B (token), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4');
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
        $this->addSql('DROP TABLE monitoring_status');
        $this->addSql('DROP TABLE todos');
        $this->addSql('DROP TABLE users');
    }
}
