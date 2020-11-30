<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200328102824 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE prefilled_answers ADD structure_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE prefilled_answers ADD CONSTRAINT FK_7930840D2534008B FOREIGN KEY (structure_id) REFERENCES structure (id)');
        $this->addSql('CREATE INDEX IDX_7930840D2534008B ON prefilled_answers (structure_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE prefilled_answers DROP FOREIGN KEY FK_7930840D2534008B');
        $this->addSql('DROP INDEX IDX_7930840D2534008B ON prefilled_answers');
        $this->addSql('ALTER TABLE prefilled_answers DROP structure_id');
    }
}
