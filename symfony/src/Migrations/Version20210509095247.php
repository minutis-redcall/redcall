<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210509095247 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE operation (id INT AUTO_INCREMENT NOT NULL, operation_external_id INT NOT NULL, owner_external_id VARCHAR(64) NOT NULL, owner_email VARCHAR(80) NOT NULL, name VARCHAR(255) NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE campaign ADD operation_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE campaign ADD CONSTRAINT FK_1F1512DD44AC3583 FOREIGN KEY (operation_id) REFERENCES operation (id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_1F1512DD44AC3583 ON campaign (operation_id)');
        $this->addSql('ALTER TABLE choice ADD operation_id INT DEFAULT NULL, CHANGE communication_id communication_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE choice ADD CONSTRAINT FK_C1AB5A9244AC3583 FOREIGN KEY (operation_id) REFERENCES operation (id)');
        $this->addSql('CREATE INDEX IDX_C1AB5A9244AC3583 ON choice (operation_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE campaign DROP FOREIGN KEY FK_1F1512DD44AC3583');
        $this->addSql('ALTER TABLE choice DROP FOREIGN KEY FK_C1AB5A9244AC3583');
        $this->addSql('DROP TABLE operation');
        $this->addSql('DROP INDEX UNIQ_1F1512DD44AC3583 ON campaign');
        $this->addSql('ALTER TABLE campaign DROP operation_id');
        $this->addSql('DROP INDEX IDX_C1AB5A9244AC3583 ON choice');
        $this->addSql('ALTER TABLE choice DROP operation_id');
    }
}
