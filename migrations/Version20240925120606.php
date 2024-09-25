<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240925120606 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE INDEX status_idx ON ban_list (status)');
        $this->addSql('CREATE INDEX banned_by_id_idx ON ban_list (banned_by_id)');
        $this->addSql('CREATE INDEX banned_user_id_idx ON ban_list (banned_user_id)');
        $this->addSql('CREATE INDEX name_idx ON logs (name)');
        $this->addSql('CREATE INDEX time_idx ON logs (time)');
        $this->addSql('CREATE INDEX status_idx ON logs (status)');
        $this->addSql('CREATE INDEX user_id_idx ON logs (user_id)');
        $this->addSql('CREATE INDEX user_agent_idx ON logs (user_agent)');
        $this->addSql('CREATE INDEX ip_address_idx ON logs (ip_address)');
        $this->addSql('CREATE INDEX status_idx ON monitoring (status)');
        $this->addSql('CREATE INDEX service_name_idx ON monitoring (service_name)');
        $this->addSql('CREATE INDEX status_idx ON todos (status)');
        $this->addSql('CREATE INDEX user_id_idx ON todos (user_id)');
        $this->addSql('CREATE INDEX role_idx ON users (role)');
        $this->addSql('CREATE INDEX token_idx ON users (token)');
        $this->addSql('CREATE INDEX username_idx ON users (username)');
        $this->addSql('CREATE INDEX ip_address_idx ON users (ip_address)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX status_idx ON todos');
        $this->addSql('DROP INDEX user_id_idx ON todos');
        $this->addSql('DROP INDEX status_idx ON ban_list');
        $this->addSql('DROP INDEX banned_by_id_idx ON ban_list');
        $this->addSql('DROP INDEX banned_user_id_idx ON ban_list');
        $this->addSql('DROP INDEX status_idx ON monitoring');
        $this->addSql('DROP INDEX service_name_idx ON monitoring');
        $this->addSql('DROP INDEX role_idx ON users');
        $this->addSql('DROP INDEX token_idx ON users');
        $this->addSql('DROP INDEX username_idx ON users');
        $this->addSql('DROP INDEX ip_address_idx ON users');
        $this->addSql('DROP INDEX name_idx ON logs');
        $this->addSql('DROP INDEX time_idx ON logs');
        $this->addSql('DROP INDEX status_idx ON logs');
        $this->addSql('DROP INDEX user_id_idx ON logs');
        $this->addSql('DROP INDEX user_agent_idx ON logs');
        $this->addSql('DROP INDEX ip_address_idx ON logs');
    }
}
