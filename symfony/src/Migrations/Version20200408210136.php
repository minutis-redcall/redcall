<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200408210136 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE twilio_call (id INT AUTO_INCREMENT NOT NULL, started_at DATETIME DEFAULT NULL, ended_at DATETIME DEFAULT NULL, duration INT DEFAULT NULL, uuid VARCHAR(36) NOT NULL, direction VARCHAR(16) NOT NULL, message VARCHAR(4096) DEFAULT NULL, from_number VARCHAR(16) NOT NULL, to_number VARCHAR(16) NOT NULL, sid VARCHAR(64) NOT NULL, status VARCHAR(20) DEFAULT NULL, price VARCHAR(255) DEFAULT NULL, unit VARCHAR(255) DEFAULT NULL, retry INT NOT NULL, context LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX sid_idx (sid), INDEX price_idx (price), UNIQUE INDEX uuid_idx (uuid), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE twilio_message ADD retry INT NOT NULL, CHANGE message message VARCHAR(4096) DEFAULT NULL, CHANGE sid sid VARCHAR(64) NOT NULL');
        $this->addSql('ALTER TABLE twilio_status ADD type VARCHAR(32) NOT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE twilio_call');
        $this->addSql('ALTER TABLE twilio_message DROP retry, CHANGE message message VARCHAR(4096) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, CHANGE sid sid VARCHAR(64) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE twilio_status DROP type');
    }
}
