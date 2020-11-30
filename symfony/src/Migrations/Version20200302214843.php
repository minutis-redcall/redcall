<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200302214843 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE twilio_message ADD created_at DATETIME NOT NULL DEFAULT \'2020-01-01\', ADD updated_at DATETIME NOT NULL DEFAULT \'2020-01-01\'');
        $this->addSql('CREATE INDEX sid_idx ON twilio_message (sid)');
        $this->addSql('CREATE INDEX price_idx ON twilio_message (price)');
        $this->addSql('CREATE UNIQUE INDEX uuid_idx ON twilio_message (uuid)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP INDEX sid_idx ON twilio_message');
        $this->addSql('DROP INDEX price_idx ON twilio_message');
        $this->addSql('DROP INDEX uuid_idx ON twilio_message');
        $this->addSql('ALTER TABLE twilio_message DROP created_at, DROP updated_at');
    }
}
