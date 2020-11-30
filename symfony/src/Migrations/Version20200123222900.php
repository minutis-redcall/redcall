<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200123222900 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP INDEX web_codex ON message');
        $this->addSql('ALTER TABLE message ADD prefix VARCHAR(255) NOT NULL DEFAULT "A", CHANGE web_code code VARBINARY(8) DEFAULT NULL');
        $this->addSql('CREATE INDEX codex ON message (code)');
        $this->addSql('ALTER TABLE communication DROP prefix');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE communication ADD prefix VARCHAR(255) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
        $this->addSql('DROP INDEX codex ON message');
        $this->addSql('ALTER TABLE message DROP prefix, CHANGE code web_code VARBINARY(8) DEFAULT NULL');
        $this->addSql('CREATE INDEX web_codex ON message (web_code)');
    }
}
