<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20181104213106 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE IF NOT EXISTS volunteer_import (id INT AUTO_INCREMENT NOT NULL, nivol VARCHAR(255) DEFAULT NULL, first_name VARCHAR(255) DEFAULT NULL, last_name VARCHAR(255) DEFAULT NULL, is_minor TINYINT(1) NOT NULL, phone VARCHAR(255) DEFAULT NULL, email VARCHAR(255) DEFAULT NULL, has_psc1 TINYINT(1) NOT NULL, has_pse1 TINYINT(1) NOT NULL, has_pse1r TINYINT(1) NOT NULL, has_pse2 TINYINT(1) NOT NULL, has_pse2r TINYINT(1) NOT NULL, has_drvr_vl TINYINT(1) NOT NULL, has_drvr_vpsp TINYINT(1) NOT NULL, has_ci TINYINT(1) NOT NULL, has_ci_r TINYINT(1) NOT NULL, is_callable TINYINT(1) NOT NULL, is_importable TINYINT(1) NOT NULL, status TEXT DEFAULT NULL COMMENT \'(DC2Type:json_array)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE volunteer ADD nivol VARCHAR(80) NOT NULL, DROP errors');
        $this->addSql('UPDATE volunteer SET nivol = CONCAT("nivol_", id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_5140DEDB5013C841 ON volunteer (nivol)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE volunteer_import');
        $this->addSql('DROP INDEX UNIQ_5140DEDB5013C841 ON volunteer');
        $this->addSql('ALTER TABLE volunteer ADD errors TEXT DEFAULT NULL COMMENT \'(DC2Type:json_array)\', DROP nivol');
    }
}
