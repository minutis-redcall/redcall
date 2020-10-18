<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200115185746 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE structure ADD parent_structure_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE structure ADD CONSTRAINT FK_6F0137EA755A5DA5 FOREIGN KEY (parent_structure_id) REFERENCES structure (id)');
        $this->addSql('CREATE INDEX IDX_6F0137EA755A5DA5 ON structure (parent_structure_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE structure DROP FOREIGN KEY FK_6F0137EA755A5DA5');
        $this->addSql('DROP INDEX IDX_6F0137EA755A5DA5 ON structure');
        $this->addSql('ALTER TABLE structure DROP parent_structure_id');
    }
}
