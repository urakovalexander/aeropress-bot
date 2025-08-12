<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250801184415 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create users table for Telegram bot';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE users (
            id SERIAL PRIMARY KEY,
            telegram_id BIGINT NOT NULL UNIQUE,
            language_code VARCHAR(10) DEFAULT NULL,
            first_name VARCHAR(255) DEFAULT NULL,
            username VARCHAR(255) DEFAULT NULL
        )');
        
        // Создаем индекс для быстрого поиска по telegram_id
        $this->addSql('CREATE INDEX idx_users_telegram_id ON users (telegram_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX idx_users_telegram_id');
        $this->addSql('DROP TABLE users');
    }
}
