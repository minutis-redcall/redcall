<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210409055155 extends AbstractMigration
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

        $this->addSql('DROP INDEX pf_extid_idx ON volunteer');
        $this->addSql('ALTER TABLE volunteer ADD external_id VARCHAR(64) NULL DEFAULT NULL');
        $this->addSql('UPDATE volunteer SET external_id = nivol');
        $this->addSql('ALTER TABLE volunteer CHANGE external_id external_id VARCHAR(64) NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX pf_extid_idx ON volunteer (platform, external_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP INDEX pf_extid_idx ON volunteer');
        $this->addSql('ALTER TABLE volunteer DROP external_id');
        $this->addSql('CREATE UNIQUE INDEX pf_extid_idx ON volunteer (platform, identifier)');
    }
}
