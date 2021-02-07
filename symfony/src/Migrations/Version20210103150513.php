<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210103150513 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE stat_chart (id INT AUTO_INCREMENT NOT NULL, page_id INT NOT NULL, query_id INT NOT NULL, name VARCHAR(255) NOT NULL, priority INT NOT NULL, options LONGTEXT NOT NULL, created_at DATETIME NOT NULL, INDEX IDX_8971F73CC4663E4 (page_id), INDEX IDX_8971F73CEF946F99 (query_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE stat_page (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, priority INT NOT NULL, created_at DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE stat_query (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, query LONGTEXT NOT NULL, context LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE stat_chart ADD CONSTRAINT FK_8971F73CC4663E4 FOREIGN KEY (page_id) REFERENCES stat_page (id)');
        $this->addSql('ALTER TABLE stat_chart ADD CONSTRAINT FK_8971F73CEF946F99 FOREIGN KEY (query_id) REFERENCES stat_query (id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE stat_chart DROP FOREIGN KEY FK_8971F73CC4663E4');
        $this->addSql('ALTER TABLE stat_chart DROP FOREIGN KEY FK_8971F73CEF946F99');
        $this->addSql('DROP TABLE stat_chart');
        $this->addSql('DROP TABLE stat_page');
        $this->addSql('DROP TABLE stat_query');
    }
}
