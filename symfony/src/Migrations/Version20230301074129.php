<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230301074129 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP INDEX external_idx ON pegass');
        $this->addSql('CREATE INDEX external_idx ON pegass (external_id)');
        $this->addSql('ALTER TABLE twilio_call CHANGE from_number from_number VARCHAR(32) NOT NULL');
        $this->addSql('ALTER TABLE volunteer ADD minor TINYINT(1) NOT NULL DEFAULT 0, DROP birthday');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP INDEX external_idx ON pegass');
        $this->addSql('CREATE INDEX external_idx ON pegass (enabled)');
        $this->addSql('ALTER TABLE twilio_call CHANGE from_number from_number VARCHAR(16) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('ALTER TABLE volunteer ADD birthday DATETIME DEFAULT NULL, DROP minor');
    }
}
