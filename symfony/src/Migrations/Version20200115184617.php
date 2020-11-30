<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20200115184617 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE volunteer DROP FOREIGN KEY FK_5140DEDB32C8A3DE');
        $this->addSql('CREATE TABLE structure (id INT AUTO_INCREMENT NOT NULL, code INT NOT NULL, type VARCHAR(16) NOT NULL, name VARCHAR(255) NOT NULL, last_volunteer_import DATETIME DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('INSERT INTO structure (id, code, type, name) VALUES (1, "889", "UL", "UNITE LOCALE DE PARIS 1ER ET 2EME")');
        $this->addSql('DROP TABLE organization');
        $this->addSql('DROP INDEX IDX_5140DEDB32C8A3DE ON volunteer');
        $this->addSql('ALTER TABLE volunteer CHANGE organization_id structure_id INT NOT NULL');
        $this->addSql('ALTER TABLE volunteer ADD CONSTRAINT FK_5140DEDB2534008B FOREIGN KEY (structure_id) REFERENCES structure (id)');
        $this->addSql('CREATE INDEX IDX_5140DEDB2534008B ON volunteer (structure_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE volunteer DROP FOREIGN KEY FK_5140DEDB2534008B');
        $this->addSql('CREATE TABLE organization (id INT AUTO_INCREMENT NOT NULL, code INT NOT NULL, type VARCHAR(16) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, name VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, last_volunteer_import DATETIME DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('INSERT INTO organization (id, code, type, name) VALUES (1, "889", "UL", "UNITE LOCALE DE PARIS 1ER ET 2EME")');
        $this->addSql('DROP TABLE structure');
        $this->addSql('DROP INDEX IDX_5140DEDB2534008B ON volunteer');
        $this->addSql('ALTER TABLE volunteer CHANGE structure_id organization_id INT NOT NULL');
        $this->addSql('ALTER TABLE volunteer ADD CONSTRAINT FK_5140DEDB32C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id) ON UPDATE NO ACTION ON DELETE NO ACTION');
        $this->addSql('CREATE INDEX IDX_5140DEDB32C8A3DE ON volunteer (organization_id)');
    }
}
