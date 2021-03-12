<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210308205237 extends AbstractMigration
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

        $this->addSql('ALTER TABLE user ADD platform VARCHAR(5) NOT NULL DEFAULT "fr"');
        $this->addSql('ALTER TABLE user CHANGE platform platform VARCHAR(5) NOT NULL');
        $this->addSql('CREATE INDEX platform_idx ON user (platform)');

        $this->addSql('ALTER TABLE category ADD platform VARCHAR(5) NOT NULL DEFAULT "fr"');
        $this->addSql('ALTER TABLE category CHANGE platform platform VARCHAR(5) NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX pf_extid_idx ON category (platform, external_id)');

        $this->addSql('ALTER TABLE badge ADD platform VARCHAR(5) NOT NULL DEFAULT "fr"');
        $this->addSql('ALTER TABLE badge CHANGE platform platform VARCHAR(5) NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX pf_extid_idx ON badge (platform, external_id)');

        $this->addSql('DROP INDEX identifier_idx ON structure');
        $this->addSql('ALTER TABLE structure ADD platform VARCHAR(5) NOT NULL DEFAULT "fr"');
        $this->addSql('ALTER TABLE structure CHANGE platform platform VARCHAR(5) NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX pf_extid_idx ON structure (platform, identifier)');

        $this->addSql('ALTER TABLE volunteer ADD platform VARCHAR(5) NOT NULL DEFAULT "fr"');
        $this->addSql('ALTER TABLE volunteer CHANGE platform platform VARCHAR(5) NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX pf_extid_idx ON volunteer (platform, identifier)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP INDEX platform_idx ON user');
        $this->addSql('ALTER TABLE user DROP platform');

        $this->addSql('DROP INDEX pf_extid_idx ON category');
        $this->addSql('ALTER TABLE category DROP platform');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_64C19C19F75D7B0 ON category (external_id)');

        $this->addSql('DROP INDEX pf_extid_idx ON badge');
        $this->addSql('ALTER TABLE badge DROP platform');

        $this->addSql('DROP INDEX pf_extid_idx ON structure');
        $this->addSql('ALTER TABLE structure DROP platform');
        $this->addSql('CREATE UNIQUE INDEX identifier_idx ON structure (identifier)');

        $this->addSql('DROP INDEX pf_extid_idx ON volunteer');
        $this->addSql('ALTER TABLE volunteer DROP platform');
    }
}
