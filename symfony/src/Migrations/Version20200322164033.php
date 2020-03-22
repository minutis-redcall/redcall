<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200322164033 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP INDEX name_idx ON structure');
        $this->addSql('CREATE INDEX name_idx ON structure (name)');
        $this->addSql('ALTER TABLE message ADD updated_at DATETIME NOT NULL DEFAULT "2020-01-01 00:00:00"');
        $this->addSql('DROP INDEX price_idx ON twilio_message');
        $this->addSql('CREATE INDEX price_idx ON twilio_message (price)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE message DROP updated_at');
        $this->addSql('DROP INDEX name_idx ON structure');
        $this->addSql('CREATE INDEX name_idx ON structure (name(191))');
        $this->addSql('DROP INDEX price_idx ON twilio_message');
        $this->addSql('CREATE INDEX price_idx ON twilio_message (price(191))');
    }
}
