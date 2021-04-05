<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210323215553 extends AbstractMigration
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

        $this->addSql('ALTER TABLE badge DROP FOREIGN KEY FK_FEF0481D727ACA70');
        $this->addSql('ALTER TABLE badge DROP enabled');
        $this->addSql('ALTER TABLE badge ADD CONSTRAINT FK_FEF0481D727ACA70 FOREIGN KEY (parent_id) REFERENCES badge (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE badge DROP FOREIGN KEY FK_FEF0481D727ACA70');
        $this->addSql('ALTER TABLE badge ADD enabled TINYINT(1) DEFAULT \'1\' NOT NULL');
        $this->addSql('ALTER TABLE badge ADD CONSTRAINT FK_FEF0481D727ACA70 FOREIGN KEY (parent_id) REFERENCES badge (id)');
    }
}
