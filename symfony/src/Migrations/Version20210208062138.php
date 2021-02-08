<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210208062138 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE stat_visualization (id INT AUTO_INCREMENT NOT NULL, page_id INT NOT NULL, query_id INT NOT NULL, name VARCHAR(255) NOT NULL, priority INT NOT NULL, options LONGTEXT NOT NULL, created_at DATETIME NOT NULL, INDEX IDX_D63899ABC4663E4 (page_id), INDEX IDX_D63899ABEF946F99 (query_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE stat_visualization ADD CONSTRAINT FK_D63899ABC4663E4 FOREIGN KEY (page_id) REFERENCES stat_page (id)');
        $this->addSql('ALTER TABLE stat_visualization ADD CONSTRAINT FK_D63899ABEF946F99 FOREIGN KEY (query_id) REFERENCES stat_query (id)');
        $this->addSql('DROP TABLE stat_chart');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE stat_chart (id INT AUTO_INCREMENT NOT NULL, page_id INT NOT NULL, query_id INT NOT NULL, name VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, priority INT NOT NULL, options LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_unicode_ci`, created_at DATETIME NOT NULL, INDEX IDX_8971F73CC4663E4 (page_id), INDEX IDX_8971F73CEF946F99 (query_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8 COLLATE `utf8_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
        $this->addSql('ALTER TABLE stat_chart ADD CONSTRAINT FK_8971F73CC4663E4 FOREIGN KEY (page_id) REFERENCES stat_page (id)');
        $this->addSql('ALTER TABLE stat_chart ADD CONSTRAINT FK_8971F73CEF946F99 FOREIGN KEY (query_id) REFERENCES stat_query (id)');
        $this->addSql('DROP TABLE stat_visualization');
    }
}
