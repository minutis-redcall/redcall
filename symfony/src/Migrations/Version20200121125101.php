<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200121125101 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE pegass DROP INDEX type_identifier_parent_idx, ADD UNIQUE INDEX typ_ide_par_idx (type, identifier, parent_identifier)');
        $this->addSql('DROP INDEX type_identifier_idx ON pegass');
        $this->addSql('ALTER TABLE pegass CHANGE enabled enabled TINYINT(1) NOT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE pegass DROP INDEX typ_ide_par_idx, ADD INDEX type_identifier_parent_idx (type, identifier, parent_identifier)');
        $this->addSql('ALTER TABLE pegass CHANGE enabled enabled TINYINT(1) DEFAULT \'1\' NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX type_identifier_idx ON pegass (type, identifier)');
    }
}
