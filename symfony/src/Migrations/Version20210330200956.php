<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210330200956 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function isTransactional() : bool
    {
        return false;
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP INDEX UNIQ_FEF0481D9F75D7B0 ON badge');
        $this->addSql('ALTER TABLE structure ADD external_id VARCHAR(64) NOT NULL');
        $this->addSql('UPDATE structure SET external_id = identifier');
        $this->addSql('ALTER TABLE structure CHANGE name name VARCHAR(64) NOT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE UNIQUE INDEX UNIQ_FEF0481D9F75D7B0 ON badge (external_id)');
        $this->addSql('ALTER TABLE structure DROP external_id');
        $this->addSql('ALTER TABLE structure CHANGE name name VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`');
    }
}
