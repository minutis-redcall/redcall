<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190531060443 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE organization (id INT AUTO_INCREMENT NOT NULL, code INT NOT NULL, type VARCHAR(16) NOT NULL, name VARCHAR(255) NOT NULL, last_volunteer_import DATETIME DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE volunteer ADD organization_id INT NOT NULL, CHANGE phone_number phone_number VARCHAR(20) DEFAULT NULL');

        $this->addSql('INSERT INTO organization (id, code, type, name) VALUES (1, "889", "UL", "UNITE LOCALE DE PARIS 1ER ET 2EME")');
        $this->addSql('UPDATE volunteer SET organization_id = 1');

        $this->addSql('ALTER TABLE volunteer ADD CONSTRAINT FK_5140DEDB32C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id)');
        $this->addSql('CREATE INDEX IDX_5140DEDB32C8A3DE ON volunteer (organization_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE volunteer DROP FOREIGN KEY FK_5140DEDB32C8A3DE');
        $this->addSql('DROP TABLE organization');
        $this->addSql('DROP INDEX IDX_5140DEDB32C8A3DE ON volunteer');
        $this->addSql('ALTER TABLE volunteer DROP organization_id, CHANGE phone_number phone_number VARCHAR(20) NOT NULL COLLATE utf8mb4_unicode_ci');
    }
}
