<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200311085015 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE cost (id INT AUTO_INCREMENT NOT NULL, message_id INT DEFAULT NULL, direction VARCHAR(255) NOT NULL, from_number VARCHAR(16) NOT NULL, to_number VARCHAR(16) NOT NULL, body LONGTEXT NOT NULL, price VARCHAR(16) NOT NULL, currency VARCHAR(3) NOT NULL, created_at DATETIME NOT NULL, INDEX IDX_182694FC537A1329 (message_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE cost ADD CONSTRAINT FK_182694FC537A1329 FOREIGN KEY (message_id) REFERENCES message (id)');
        $this->addSql('ALTER TABLE message DROP cost, DROP currency');
        $this->addSql('DROP INDEX name_idx ON structure');
        $this->addSql('CREATE INDEX name_idx ON structure (name)');
        $this->addSql('DROP INDEX price_idx ON twilio_message');
        $this->addSql('CREATE INDEX price_idx ON twilio_message (price)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE cost');
        $this->addSql('ALTER TABLE message ADD cost DOUBLE PRECISION NOT NULL, ADD currency VARCHAR(3) CHARACTER SET utf8mb4 DEFAULT \'EUR\' NOT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('DROP INDEX name_idx ON structure');
        $this->addSql('CREATE INDEX name_idx ON structure (name(191))');
        $this->addSql('DROP INDEX price_idx ON twilio_message');
        $this->addSql('CREATE INDEX price_idx ON twilio_message (price(191))');
    }
}
