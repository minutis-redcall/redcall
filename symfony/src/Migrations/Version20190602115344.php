<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190602115344 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE volunteer_import');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE volunteer_import (id INT AUTO_INCREMENT NOT NULL, nivol VARCHAR(255) DEFAULT NULL COLLATE utf8mb4_unicode_ci, first_name VARCHAR(255) DEFAULT NULL COLLATE utf8mb4_unicode_ci, last_name VARCHAR(255) DEFAULT NULL COLLATE utf8mb4_unicode_ci, is_minor TINYINT(1) DEFAULT NULL, phone VARCHAR(255) DEFAULT NULL COLLATE utf8mb4_unicode_ci, email VARCHAR(255) DEFAULT NULL COLLATE utf8mb4_unicode_ci, is_callable TINYINT(1) NOT NULL, is_importable TINYINT(1) NOT NULL, status JSON DEFAULT NULL COMMENT \'(DC2Type:json_array)\', tags JSON DEFAULT NULL COMMENT \'(DC2Type:json_array)\', postal_code VARCHAR(255) DEFAULT NULL COLLATE utf8mb4_unicode_ci, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE utf8_unicode_ci ENGINE = InnoDB COMMENT = \'\' ');
    }
}
