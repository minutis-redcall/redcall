<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210318052545 extends AbstractMigration
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

        $this->addSql('ALTER TABLE badge ADD enabled TINYINT(1) DEFAULT \'1\' NOT NULL, ADD locked TINYINT(1) DEFAULT \'0\' NOT NULL');
        $this->addSql('ALTER TABLE category ADD enabled TINYINT(1) DEFAULT \'1\' NOT NULL, ADD locked TINYINT(1) DEFAULT \'0\' NOT NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE badge DROP enabled, DROP locked');
        $this->addSql('ALTER TABLE category DROP enabled, DROP locked');
    }
}